<?php

namespace Tests\Feature\Dte;

use App\Actions\Sales\CreateSale;
use App\Exceptions\SaleValidationException;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Stockia\DteSvMh\Models\Dte;
use Stockia\DteSvMh\Models\DteEstablecimiento;
use Stockia\DteSvMh\Schemas\DteSchemaRegistry;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;
use Stockia\DteSvMh\Services\DteEmissionService;
use Tests\TestCase;

/** Factura de Exportación (11) desde una venta y Factura de Sujeto Excluido (14) desde una compra. */
class ExportacionSujetoExcluidoFlujoTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Warehouse $warehouse;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $currency = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dolar', 'symbol' => '$']);
        $this->company = Company::create([
            'name' => 'Empresa', 'currency_id' => $currency->id, 'nit' => '0512-280887-101-3', 'nrc' => '23119-4',
            'cod_actividad' => '47112', 'desc_actividad' => 'Venta al por menor', 'departamento' => '06', 'municipio' => '21',
            'distrito' => '01', 'direccion_complemento' => 'Calle Principal 1', 'telefono' => '22223333', 'correo' => 'v@ejemplo.com.sv',
        ]);
        $e = DteEstablecimiento::create(['company_id' => $this->company->id, 'codigo' => 'M001', 'tipo_establecimiento' => '02', 'nombre' => 'Matriz']);
        $e->puntosVenta()->create(['codigo' => 'P001', 'nombre' => 'Caja']);
        $this->warehouse = Warehouse::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'name' => 'B', 'code' => 'B-1', 'is_active' => true]);

        $this->user = User::factory()->create();
        $cuId = DB::table('company_user')->insertGetId(['company_id' => $this->company->id, 'user_id' => $this->user->id, 'role' => 'SuperAdmin', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('company_user_warehouse')->insert(['company_user_id' => $cuId, 'warehouse_id' => $this->warehouse->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function producto(string $nombre, float $precio = 100, int $stock = 50): Product
    {
        $p = Product::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'name' => $nombre.' '.uniqid(), 'price' => $precio, 'stock' => 0]);
        DB::table('product_warehouse')->insert(['company_id' => $this->company->id, 'product_id' => $p->id, 'warehouse_id' => $this->warehouse->id, 'stock' => $stock, 'created_at' => now(), 'updated_at' => now()]);

        return $p;
    }

    private function entrar(): void
    {
        $this->actingAs($this->user)->withSession(['current_company_id' => $this->company->id, 'current_warehouse_id' => $this->warehouse->id]);
    }

    private function validar(array $dte, string $clave): array
    {
        return (new DteSchemaValidator(new DteSchemaRegistry()))->validar($dte, $clave);
    }

    // ---------------------------------------------------------------- Exportación

    private function clienteExtranjero(array $extra = []): Customer
    {
        return Customer::withoutGlobalScopes()->create($extra + [
            'company_id' => $this->company->id, 'tipo_documento' => '03', 'numero_documento' => 'X1234567', 'nombre' => 'ACME INC',
            'direccion' => '5th Avenue 100, New York', 'cod_actividad' => '46900', 'desc_actividad' => 'Comercio al por mayor',
            'cod_pais' => 'US', 'nombre_pais' => 'Estados Unidos', 'tipo_persona' => 2, 'telefono' => '+12125550100', 'correo' => 'buyer@acme.com',
            'es_contribuyente' => false,
        ]);
    }

    private function configurarExportacion(array $extra = []): void
    {
        DB::table('dte_settings')->updateOrInsert(['company_id' => $this->company->id], $extra + [
            'enabled' => true, 'integration_mode' => 'simulacion', 'ambiente' => '00',
            'expo_tipo_item' => 1, 'expo_recinto' => '01', 'expo_tipo_regimen' => 'EX-1', 'expo_regimen' => '1000.000', 'expo_cod_incoterms' => '09',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ventaExportacion(?Customer $cliente = null, int $cantidad = 3): Sale
    {
        $p = $this->producto('Cafe', 20.0);
        $cliente ??= $this->clienteExtranjero();

        return app(CreateSale::class)->handle([
            'company_id' => $this->company->id, 'warehouse_id' => $this->warehouse->id, 'user_id' => $this->user->id,
            'products' => [$p->id => $cantidad], 'document_type' => 'factura', 'tipo_dte' => '11',
            'customer_id' => $cliente->id, 'cash_received' => 1000,
        ]);
    }

    public function test_la_venta_de_exportacion_no_lleva_iva_y_emite_la_factura_11(): void
    {
        $this->configurarExportacion();
        $sale = $this->ventaExportacion();

        $this->assertEquals(0.0, (float) $sale->iva);
        $this->assertEquals(60.0, (float) $sale->total, 'sin IVA: 3 × 20');

        $dte = Dte::where('sale_id', $sale->id)->first() ?? app(DteEmissionService::class)->emitForSale($sale->fresh());
        $this->assertSame(['11', 'ACEPTADO'], [$dte->tipo_dte, $dte->estado]);

        $json = $dte->json_original;
        $this->assertSame([], $this->validar($json, '11'));
        $this->assertEquals(60.0, $json['resumen']['totalGravada']);
        $this->assertEquals(60.0, $json['resumen']['totalPagar']);
        $this->assertSame('US', $json['receptor']['codPais']);
        $this->assertSame(1, $json['emisor']['tipoItemExpor']);
        $this->assertSame('1000.000', $json['emisor']['regimen']);
        $this->assertSame('FOB-Libre a bordo', $json['resumen']['descIncoterms']);
        $this->assertSame(['C3'], $json['cuerpoDocumento'][0]['tributos']);
    }

    public function test_exportacion_de_servicios_no_pide_recinto_ni_regimen(): void
    {
        $this->configurarExportacion(['expo_tipo_item' => 2, 'expo_recinto' => null, 'expo_tipo_regimen' => null, 'expo_regimen' => null]);
        $sale = $this->ventaExportacion();
        $dte = Dte::where('sale_id', $sale->id)->first() ?? app(DteEmissionService::class)->emitForSale($sale->fresh());

        $this->assertSame([], $this->validar($dte->json_original, '11'));
        $this->assertNull($dte->json_original['emisor']['recintoFiscal']);
    }

    public function test_sin_datos_de_exportacion_en_la_empresa_no_se_emite(): void
    {
        $sale = $this->ventaExportacion();
        Dte::where('sale_id', $sale->id)->delete();

        $this->expectExceptionMessage('datos de exportación');
        app(DteEmissionService::class)->emitForSale($sale->fresh());
    }

    public function test_el_cliente_sin_pais_no_puede_comprar_exportacion(): void
    {
        $this->configurarExportacion();
        $this->expectException(SaleValidationException::class);
        $this->ventaExportacion($this->clienteExtranjero(['cod_pais' => null, 'nombre_pais' => null, 'numero_documento' => 'Y7654321']));
    }

    public function test_la_exportacion_exige_cliente(): void
    {
        $p = $this->producto('Cafe');
        $this->expectExceptionMessage('cliente del extranjero');
        app(CreateSale::class)->handle([
            'company_id' => $this->company->id, 'warehouse_id' => $this->warehouse->id, 'user_id' => $this->user->id,
            'products' => [$p->id => 1], 'document_type' => 'factura', 'tipo_dte' => '11', 'cash_received' => 1000,
        ]);
    }

    public function test_se_invalida_una_exportacion_con_evento_valido(): void
    {
        $this->configurarExportacion();
        $sale = $this->ventaExportacion();
        $dte = Dte::where('sale_id', $sale->id)->first() ?? app(DteEmissionService::class)->emitForSale($sale->fresh());

        app(DteEmissionService::class)->invalidate($dte, '2', 'Operación rescindida');

        $this->assertSame('INVALIDADO', $dte->fresh()->estado);
        $evento = json_decode(DB::table('dte_invalidaciones')->where('dte_id', $dte->id)->value('json_evento'), true);
        $this->assertSame([], $this->validar($evento, 'invalidacion'));
        $this->assertSame('11', $evento['documento']['tipoDte']);
        $this->assertSame('X1234567', $evento['documento']['numDocumento']);
    }

    // ---------------------------------------------------------------- Sujeto excluido

    private function compra(array $proveedor = [], array $compra = []): Purchase
    {
        $prov = Provider::withoutGlobalScopes()->create($proveedor + [
            'company_id' => $this->company->id, 'nombre' => 'MARIA HERNANDEZ', 'dui' => '012345678', 'telefono' => '7777-8888',
            'direccion' => 'Col. Zacamil, pasaje 3', 'departamento' => '01', 'municipio' => '0114', 'distrito' => '01',
        ]);
        $p = $this->producto('Hortaliza');

        $purchase = Purchase::create($compra + [
            'company_id' => $this->company->id, 'warehouse_id' => $this->warehouse->id, 'provider_id' => $prov->id, 'user_id' => $this->user->id,
            'numero_doc' => 'C-'.uniqid(), 'fecha' => now()->toDateString(), 'subtotal' => 50, 'gravadas' => 0, 'exentas' => 50, 'no_sujetas' => 0, 'iva' => 0, 'total' => 50,
        ]);
        PurchaseItem::create(['purchase_id' => $purchase->id, 'product_id' => $p->id, 'cantidad' => 20, 'costo_unitario' => 2.5, 'subtotal' => 50]);

        return $purchase->fresh(['provider', 'items.product']);
    }

    public function test_compra_a_sujeto_excluido_emite_la_factura_14_valida(): void
    {
        $compra = $this->compra();
        $dte = app(DteEmissionService::class)->emitirSujetoExcluido($compra, 5.0, 'Compra de hortalizas');

        $this->assertSame(['14', 'ACEPTADO', $compra->id], [$dte->tipo_dte, $dte->estado, (int) $dte->purchase_id]);
        $this->assertNull($dte->sale_id);

        $json = $dte->json_original;
        $this->assertSame([], $this->validar($json, '14'));
        $this->assertSame('13', $json['receptor']['tipoDocumento']);
        $this->assertSame('012345678', $json['receptor']['numDocumento']);
        $this->assertEquals(50.0, $json['resumen']['totalCompra']);
        $this->assertEquals(5.0, $json['resumen']['reteRenta']);
        $this->assertEquals(45.0, $json['resumen']['totalPagar']);
        $this->assertSame('01', $json['receptor']['direccion']['departamento']);
        $this->assertSame('14', $json['receptor']['direccion']['municipio']);
        $this->assertSame('77778888', $json['receptor']['telefono']);
    }

    public function test_una_compra_no_admite_dos_facturas_de_sujeto_excluido(): void
    {
        $compra = $this->compra();
        app(DteEmissionService::class)->emitirSujetoExcluido($compra);

        $this->expectExceptionMessage('ya tiene una Factura de Sujeto Excluido');
        app(DteEmissionService::class)->emitirSujetoExcluido($compra);
    }

    public function test_el_proveedor_sin_documento_o_direccion_no_se_acepta(): void
    {
        try {
            app(DteEmissionService::class)->emitirSujetoExcluido($this->compra(['dui' => null, 'nit' => null]));
            $this->fail('Sin documento no debe emitir');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('DUI o NIT', $e->getMessage());
        }

        $this->expectExceptionMessage('departamento, municipio, distrito y dirección');
        app(DteEmissionService::class)->emitirSujetoExcluido($this->compra(['distrito' => null]));
    }

    public function test_un_distrito_incoherente_con_el_municipio_se_rechaza(): void
    {
        $this->expectExceptionMessage('no corresponde');
        app(DteEmissionService::class)->emitirSujetoExcluido($this->compra(['municipio' => '0113'])); // distrito 01 de Ahuachapán es de Ahuachapán Centro (14), no de Norte (13)
    }

    public function test_usa_nit_si_el_proveedor_no_tiene_dui(): void
    {
        $dte = app(DteEmissionService::class)->emitirSujetoExcluido($this->compra(['dui' => null, 'nit' => '0614-123456-789-0']));
        $this->assertSame('36', $dte->json_original['receptor']['tipoDocumento']);
        $this->assertSame([], $this->validar($dte->json_original, '14'));
    }

    public function test_pantalla_y_controlador_de_sujeto_excluido(): void
    {
        $compra = $this->compra(['departamento' => null, 'municipio' => null, 'distrito' => null, 'direccion' => null]);
        $this->entrar();

        $this->get(route('purchases.show', $compra))->assertOk()->assertSee('Emitir factura de sujeto excluido', false);
        $this->get(route('purchases.sujeto-excluido', $compra))->assertOk()->assertSee('Dirección del vendedor', false);

        // Sin dirección: error y nada se emite.
        $this->post(route('purchases.sujeto-excluido.store', $compra), ['departamento' => '01'])->assertSessionHasErrors(['municipio', 'distrito', 'direccion']);
        $this->assertSame(0, Dte::where('purchase_id', $compra->id)->count());

        $this->post(route('purchases.sujeto-excluido.store', $compra), [
            'departamento' => '01', 'municipio' => '0114', 'distrito' => '01', 'direccion' => 'Col. Escalón 10', 'retencion_renta' => '2.50',
        ])->assertSessionHasNoErrors()->assertRedirect(route('purchases.show', $compra));

        $dte = Dte::where('purchase_id', $compra->id)->first();
        $this->assertSame('ACEPTADO', $dte->estado);
        $this->assertSame('Col. Escalón 10', $compra->provider->fresh()->direccion, 'la dirección queda en el proveedor');

        $this->get(route('purchases.show', $compra))->assertSee($dte->numero_control, false)->assertDontSee('Emitir factura de sujeto excluido', false);
        $this->get(route('purchases.sujeto-excluido', $compra))->assertRedirect(route('purchases.show', $compra));
    }

    public function test_se_invalida_la_factura_de_sujeto_excluido(): void
    {
        $compra = $this->compra();
        $dte = app(DteEmissionService::class)->emitirSujetoExcluido($compra);
        $this->entrar();

        // Con "error" sin factura de reemplazo: Hacienda no lo admite.
        $this->post(route('purchases.sujeto-excluido.invalidate', $compra), ['tipo_invalidacion' => '1', 'motivo' => 'Monto errado'])->assertSessionHasErrors();
        $this->assertSame('ACEPTADO', $dte->fresh()->estado);

        $this->post(route('purchases.sujeto-excluido.invalidate', $compra), ['tipo_invalidacion' => '2', 'motivo' => 'Compra anulada'])->assertSessionHasNoErrors();
        $this->assertSame('INVALIDADO', $dte->fresh()->estado);

        $evento = json_decode(DB::table('dte_invalidaciones')->where('dte_id', $dte->id)->value('json_evento'), true);
        $this->assertSame([], $this->validar($evento, 'invalidacion'));
        $this->assertSame('14', $evento['documento']['tipoDte']);
        $this->assertSame('012345678', $evento['documento']['numDocumento']);
    }

    public function test_error_en_la_factura_de_sujeto_excluido_se_corrige_con_otra_compra(): void
    {
        $mala = $this->compra();
        $dteMala = app(DteEmissionService::class)->emitirSujetoExcluido($mala);
        $buena = $this->compra();
        $dteBuena = app(DteEmissionService::class)->emitirSujetoExcluido($buena);
        $this->entrar();

        $this->post(route('purchases.sujeto-excluido.invalidate', $mala), ['tipo_invalidacion' => '1', 'motivo' => 'Datos errados', 'replacement_purchase_id' => $buena->id])
            ->assertSessionHasNoErrors();

        $this->assertSame('INVALIDADO', $dteMala->fresh()->estado);
        $evento = json_decode(DB::table('dte_invalidaciones')->where('dte_id', $dteMala->id)->value('json_evento'), true);
        $this->assertSame($dteBuena->codigo_generacion, $evento['documento']['codigoGeneracionR']);
    }

    public function test_no_se_toca_la_compra_de_otra_empresa(): void
    {
        $compra = $this->compra();
        $otra = Company::create(['name' => 'Otra', 'currency_id' => $this->company->currency_id]);
        $this->actingAs($this->user)->withSession(['current_company_id' => $otra->id, 'current_warehouse_id' => $this->warehouse->id]);

        $this->get(route('purchases.sujeto-excluido', $compra))->assertNotFound();
    }
}
