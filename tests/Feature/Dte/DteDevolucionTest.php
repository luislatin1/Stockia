<?php

namespace Tests\Feature\Dte;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Stockia\DteSvMh\Devolucion\PlazoInvalidacion;
use Stockia\DteSvMh\Devolucion\RutaDevolucion;
use Stockia\DteSvMh\Models\Dte;
use Stockia\DteSvMh\Models\DteEstablecimiento;
use Stockia\DteSvMh\Schemas\DteSchemaRegistry;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;
use Stockia\DteSvMh\Services\DteEmissionService;
use Tests\TestCase;

/** Devoluciones sobre ventas con DTE: ruta hacia Hacienda según documento y motivo. */
class DteDevolucionTest extends TestCase
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
        $cuId = DB::table('company_user')->insertGetId([
            'company_id' => $this->company->id, 'user_id' => $this->user->id, 'role' => 'SuperAdmin', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('company_user_warehouse')->insert(['company_user_id' => $cuId, 'warehouse_id' => $this->warehouse->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function cliente(): Customer
    {
        return Customer::withoutGlobalScopes()->firstOrCreate(['company_id' => $this->company->id, 'tipo_documento' => '36', 'numero_documento' => '0614-123456-789-0'], [
            'company_id' => $this->company->id, 'tipo_documento' => '36', 'numero_documento' => '0614-123456-789-0', 'nrc' => '12345-6',
            'nombre' => 'CLIENTE SA', 'departamento' => '06', 'municipio' => '14', 'distrito' => '01', 'direccion' => 'Col. Escalón 5',
            'cod_actividad' => '47112', 'desc_actividad' => 'Venta al por menor', 'telefono' => '22001122', 'correo' => 'c@x.com.sv', 'es_contribuyente' => true,
        ]);
    }

    /** Venta con dos líneas netas (100 y 50) y su DTE emitido (simulación → ACEPTADO). */
    private function ventaConDte(string $tipo): Sale
    {
        $p1 = Product::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'name' => 'Camisa '.uniqid(), 'price' => 50, 'stock' => 0]);
        $p2 = Product::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'name' => 'Cinturon '.uniqid(), 'price' => 50, 'stock' => 0]);

        foreach ([$p1, $p2] as $p) {
            DB::table('product_warehouse')->insertOrIgnore(['product_id' => $p->id, 'warehouse_id' => $this->warehouse->id, 'stock' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }

        $sale = new Sale();
        $sale->forceFill([
            'company_id' => $this->company->id, 'warehouse_id' => $this->warehouse->id, 'user_id' => $this->user->id,
            'customer_id' => $tipo === '03' ? $this->cliente()->id : null, 'document_type' => 'factura', 'tipo_dte' => $tipo, 'status' => 'completed',
            'subtotal' => 150, 'gravadas' => 150, 'iva' => 19.5, 'tax_total' => 19.5, 'total' => 169.5, 'payment_method' => 'cash', 'numero_interno' => 'V-'.uniqid(),
        ])->save();

        foreach ([[$p1, 2, 50], [$p2, 1, 50]] as [$p, $q, $precio]) {
            $i = new SaleItem();
            $i->forceFill([
                'sale_id' => $sale->id, 'product_id' => $p->id, 'quantity' => $q, 'price' => $precio, 'precio_unitario' => $precio,
                'monto_gravado' => $q * $precio, 'iva_item' => $q * $precio * 0.13, 'total_item' => $q * $precio * 1.13, 'subtotal' => $q * $precio,
                'tipo_item' => 1, 'uni_medida' => 59,
            ])->save();
        }

        $dte = app(DteEmissionService::class)->emitForSale($sale->fresh());
        $this->assertSame('ACEPTADO', $dte->estado);

        return $sale->fresh(['items.product']);
    }

    private function ajuste(Sale $sale, string $accion = 'return_items'): int
    {
        return DB::table('sale_adjustments')->insertGetId([
            'sale_id' => $sale->id, 'company_id' => $this->company->id, 'warehouse_id' => $this->warehouse->id,
            'processed_by_user_id' => $this->user->id, 'action_type' => $accion, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function servicio(): \Stockia\DteSvMh\Devolucion\DteDevolucionService
    {
        return app(\Stockia\DteSvMh\Devolucion\DteDevolucionService::class);
    }

    // ---- matriz de rutas (pura) -------------------------------------------------------------

    public function test_matriz_de_rutas_para_ccf_y_factura(): void
    {
        $r = fn ($t, $m, $a) => RutaDevolucion::resolver($t, $m, $a);

        $this->assertSame('ninguna', $r(null, 'devolucion', 'total')['ruta']);
        $this->assertSame('nota_credito', $r('03', 'devolucion', 'parcial')['ruta']);
        $this->assertSame('nota_credito', $r('03', 'garantia', 'total')['ruta']);
        $this->assertSame('nota_credito', $r('03', 'error_precio', 'valor')['ruta']);
        $this->assertSame('bloqueado', $r('03', 'error_precio', 'total')['ruta']);
        $this->assertSame('invalidacion', $r('03', 'error_fecha', 'total')['ruta']);
        $this->assertSame('bloqueado', $r('03', 'error_fecha', 'parcial')['ruta']);

        $this->assertSame(['invalidacion', '2', false], [$r('01', 'devolucion', 'total')['ruta'], $r('01', 'devolucion', 'total')['tipo_invalidacion'], $r('01', 'devolucion', 'total')['requiere_reemplazo']]);
        $this->assertSame(['3', true], [$r('01', 'cambio_producto', 'total')['tipo_invalidacion'], $r('01', 'cambio_producto', 'total')['requiere_reemplazo']]);
        $this->assertSame(['1', true], [$r('01', 'error_fecha', 'total')['tipo_invalidacion'], $r('01', 'error_fecha', 'total')['requiere_reemplazo']]);
        $this->assertSame('bloqueado', $r('01', 'devolucion', 'parcial')['ruta']);
        $this->assertSame('bloqueado', $r('05', 'devolucion', 'total')['ruta']);
        $this->assertSame('bloqueado', $r('01', 'inventado', 'total')['ruta']);
    }

    public function test_plazos_de_invalidacion(): void
    {
        $z = 'America/El_Salvador';
        // Factura: tres meses desde el sello.
        $this->assertSame('2027-02-19', PlazoInvalidacion::limite('01', CarbonImmutable::parse('2026-11-19 10:00', $z))->format('Y-m-d'));
        // CCF sellado en noviembre 2026: décimo día hábil de diciembre (1-4 y 7-12 → lunes 14).
        $this->assertSame('2026-12-14', PlazoInvalidacion::limite('03', CarbonImmutable::parse('2026-11-19 10:00', $z))->format('Y-m-d'));
        $this->assertTrue(PlazoInvalidacion::vigente('03', CarbonImmutable::parse('2026-11-19', $z), CarbonImmutable::parse('2026-12-14 23:59:00', $z)));
        $this->assertFalse(PlazoInvalidacion::vigente('03', CarbonImmutable::parse('2026-11-19', $z), CarbonImmutable::parse('2026-12-15 00:00:01', $z)));
    }

    // ---- Nota de Crédito sobre CCF ---------------------------------------------------------

    public function test_devolucion_parcial_de_ccf_emite_nota_de_credito_valida(): void
    {
        $sale = $this->ventaConDte('03');
        $camisa = $sale->items->first(); // 2 × 50
        $id = $this->ajuste($sale);

        $res = $this->servicio()->ejecutar($sale, $id, 'devolucion', 'parcial', 'return_items',
            [['sale_item_id' => $camisa->id, 'quantity' => 1]], null, 'cliente se arrepintió');

        $this->assertSame('nota_credito', $res['ruta']);
        $nc = $res['dte'];
        $this->assertSame('05', $nc->tipo_dte);
        $this->assertSame('ACEPTADO', $nc->estado);
        $origen = Dte::where('sale_id', $sale->id)->where('tipo_dte', '03')->first();
        $this->assertSame($origen->id, (int) $nc->dte_origen_id);

        $json = $nc->json_original;
        $this->assertSame([], (new DteSchemaValidator(new DteSchemaRegistry()))->validar($json, '05'));
        $this->assertSame($origen->codigo_generacion, $json['documentoRelacionado'][0]['numeroDocumento']);
        $this->assertEquals(50.0, $json['resumen']['totalGravada']);
        $this->assertEquals(56.5, $json['resumen']['totalPagar']);
        $this->assertStringContainsString('Devolución de producto', $json['resumen']['observaciones']);

        $fila = DB::table('sale_adjustments')->find($id);
        $this->assertSame(['devolucion', 'nota_credito', $nc->id], [$fila->motivo, $fila->dte_ruta, (int) $fila->dte_id]);
    }

    public function test_no_se_puede_devolver_mas_de_lo_que_queda(): void
    {
        $sale = $this->ventaConDte('03');
        $camisa = $sale->items->first();

        $id1 = $this->ajuste($sale);
        DB::table('sale_adjustment_items')->insert(['sale_adjustment_id' => $id1, 'sale_item_id' => $camisa->id, 'product_id' => $camisa->product_id,
            'quantity' => 2, 'product_condition' => 'unopened', 'restocked' => true, 'created_at' => now(), 'updated_at' => now()]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Solo quedan 0');
        $this->servicio()->ejecutar($sale, $this->ajuste($sale), 'devolucion', 'parcial', 'return_items',
            [['sale_item_id' => $camisa->id, 'quantity' => 1]], null, '');
    }

    public function test_ajuste_de_valor_en_ccf_acredita_monto_sin_mover_cantidades(): void
    {
        $sale = $this->ventaConDte('03');
        $cinturon = $sale->items->last(); // 1 × 50
        $res = $this->servicio()->ejecutar($sale, $this->ajuste($sale), 'descuento_omitido', 'valor', 'return_items',
            [['sale_item_id' => $cinturon->id, 'quantity' => 0, 'credit_amount' => 10.0]], null, '');

        $json = $res['dte']->json_original;
        $this->assertSame([], (new DteSchemaValidator(new DteSchemaRegistry()))->validar($json, '05'));
        $this->assertEquals(10.0, $json['resumen']['totalGravada']);
        $this->assertEquals(11.3, $json['resumen']['totalPagar']);
    }

    public function test_ajuste_de_valor_no_puede_superar_la_linea(): void
    {
        $sale = $this->ventaConDte('03');
        $this->expectExceptionMessage('supera lo que queda');
        $this->servicio()->ejecutar($sale, $this->ajuste($sale), 'error_precio', 'valor', 'return_items',
            [['sale_item_id' => $sale->items->last()->id, 'quantity' => 0, 'credit_amount' => 50.01]], null, '');
    }

    public function test_anular_ccf_completo_por_devolucion_acredita_todo(): void
    {
        $sale = $this->ventaConDte('03');
        $res = $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'cancel_sale'), 'devolucion', 'total', 'cancel_sale', [], null, '');

        $this->assertEquals(150.0, $res['dte']->json_original['resumen']['totalGravada']);
        $this->assertEquals(169.5, $res['dte']->json_original['resumen']['totalPagar']);
    }

    // ---- Invalidación ------------------------------------------------------------------------

    public function test_devolucion_total_de_factura_invalida_por_rescision(): void
    {
        $sale = $this->ventaConDte('01');
        $res = $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'void_invoice'), 'devolucion', 'total', 'void_invoice', [], null, '');

        $this->assertSame('invalidacion', $res['ruta']);
        $this->assertSame('INVALIDADO', Dte::where('sale_id', $sale->id)->first()->estado);
        $this->assertSame('2', DB::table('dte_invalidaciones')->value('tipo_invalidacion'));
    }

    public function test_devolucion_parcial_de_factura_se_bloquea(): void
    {
        $sale = $this->ventaConDte('01');
        $this->expectExceptionMessage('Evento de Retorno');
        $this->servicio()->ejecutar($sale, $this->ajuste($sale), 'devolucion', 'parcial', 'return_items',
            [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]], null, '');
    }

    public function test_error_de_fecha_en_factura_exige_la_venta_que_reemplaza(): void
    {
        $sale = $this->ventaConDte('01');

        try {
            $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'void_invoice'), 'error_fecha', 'total', 'void_invoice', [], null, '');
            $this->fail('Debió exigir la venta de reemplazo');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('venta que reemplaza', $e->getMessage());
        }
        $this->assertSame('ACEPTADO', Dte::where('sale_id', $sale->id)->first()->estado);

        $nueva = $this->ventaConDte('01');
        $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'void_invoice'), 'error_fecha', 'total', 'void_invoice', [], $nueva->id, '');
        $this->assertSame('INVALIDADO', Dte::where('sale_id', $sale->id)->first()->estado);
        $this->assertSame('ACEPTADO', Dte::where('sale_id', $nueva->id)->first()->estado);
        $this->assertSame('1', DB::table('dte_invalidaciones')->value('tipo_invalidacion'));
    }

    public function test_ccf_con_nota_vigente_no_se_invalida_hasta_invalidar_la_nota(): void
    {
        $sale = $this->ventaConDte('03');
        $this->servicio()->ejecutar($sale, $this->ajuste($sale), 'devolucion', 'parcial', 'return_items',
            [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]], null, '');
        $nueva = $this->ventaConDte('03');

        $this->expectExceptionMessage('notas de crédito/débito vigentes');
        $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'cancel_sale'), 'error_datos_cliente', 'total', 'cancel_sale', [], $nueva->id, '');
    }

    public function test_el_dte_vencido_no_se_invalida(): void
    {
        $sale = $this->ventaConDte('01');
        Dte::where('sale_id', $sale->id)->update(['fecha_envio' => now()->subMonths(4)]);

        $this->expectExceptionMessage('Venció el plazo');
        $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'void_invoice'), 'devolucion', 'total', 'void_invoice', [], null, '');
    }

    public function test_venta_sin_dte_no_informa_a_hacienda(): void
    {
        $sale = $this->ventaConDte('01');
        Dte::where('sale_id', $sale->id)->update(['estado' => 'RECHAZADO']);

        $res = $this->servicio()->ejecutar($sale, $this->ajuste($sale), 'devolucion', 'parcial', 'return_items', [], null, '');
        $this->assertSame('ninguna', $res['ruta']);
    }

    // ---- Pantalla / controlador -----------------------------------------------------------

    private function entrar(): void
    {
        $this->actingAs($this->user)->withSession([
            'current_company_id' => $this->company->id,
            'current_warehouse_id' => $this->warehouse->id,
        ]);
    }

    public function test_pantalla_de_ajuste_muestra_motivos_cuando_hay_dte(): void
    {
        $sale = $this->ventaConDte('03');
        $this->entrar();

        $this->get(route('sales.admin-adjustment', $sale))
            ->assertOk()
            ->assertSee('Documento tributario electrónico', false)
            ->assertSee('Garantía / desperfecto', false)
            ->assertSee('Devolver / ajustar', false);
    }

    public function test_el_controlador_exige_motivo_si_hay_dte(): void
    {
        $sale = $this->ventaConDte('03');
        $this->entrar();

        $this->from('/x')->post(route('sales.admin-adjustment.store', $sale), [
            'action_type' => 'return_items', 'return_products' => 1,
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1, 'condition' => 'unopened']],
        ])->assertSessionHasErrors();
        $this->assertSame(0, DB::table('sale_adjustments')->count());
    }

    public function test_el_controlador_devuelve_parcialmente_un_ccf_y_emite_la_nota(): void
    {
        $sale = $this->ventaConDte('03');
        $this->entrar();
        $camisa = $sale->items->first();

        $this->post(route('sales.admin-adjustment.store', $sale), [
            'action_type' => 'return_items', 'motivo' => 'garantia', 'return_products' => 1, 'refund_cash' => 1, 'refund_amount' => 56.5,
            'items' => [['sale_item_id' => $camisa->id, 'quantity' => 1, 'condition' => 'defective']],
        ])->assertSessionHasNoErrors()->assertRedirect(route('sales.show', $sale));

        $this->assertSame('completed', $sale->fresh()->status, 'devolución parcial: la venta sigue vigente');
        $this->assertSame(1, Dte::where('sale_id', $sale->id)->where('tipo_dte', '05')->count());
        $this->assertSame('defective', DB::table('non_sellable_products')->value('condition'));
        $this->assertSame('garantia', DB::table('sale_adjustments')->value('motivo'));
    }

    public function test_el_controlador_revierte_todo_si_la_ruta_esta_bloqueada_por_la_factura_parcial(): void
    {
        $sale = $this->ventaConDte('01');
        $this->entrar();

        $this->post(route('sales.admin-adjustment.store', $sale), [
            'action_type' => 'return_items', 'motivo' => 'devolucion', 'return_products' => 1,
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1, 'condition' => 'unopened']],
        ])->assertSessionHasErrors();

        $this->assertSame(0, DB::table('sale_adjustments')->count());
        $this->assertSame(0, DB::table('inventory_movements')->count());
        $this->assertSame('completed', $sale->fresh()->status);
    }

    public function test_anular_factura_por_devolucion_total_invalida_el_dte(): void
    {
        $sale = $this->ventaConDte('01');
        $this->entrar();

        $this->post(route('sales.admin-adjustment.store', $sale), [
            'action_type' => 'void_invoice', 'motivo' => 'devolucion', 'refund_cash' => 1, 'refund_amount' => 113,
        ])->assertSessionHasNoErrors();

        $this->assertSame('annulled', $sale->fresh()->status);
        $this->assertSame('INVALIDADO', Dte::where('sale_id', $sale->id)->first()->estado);
    }
}
