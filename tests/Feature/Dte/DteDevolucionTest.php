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
use Stockia\DteSvMh\Devolucion\PlazoRetorno;
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
            DB::table('product_warehouse')->insert(['company_id' => $this->company->id, 'product_id' => $p->id, 'warehouse_id' => $this->warehouse->id, 'stock' => 0, 'created_at' => now(), 'updated_at' => now()]);
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
        $r = fn ($t, $m, $a, $ret = false) => RutaDevolucion::resolver($t, $m, $a, $ret);

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
        $this->assertSame('evento_retorno', $r('01', 'devolucion', 'parcial')['ruta']);
        $this->assertSame('evento_retorno', $r('01', 'cambio_producto', 'parcial')['ruta']);
        $this->assertSame('evento_retorno', $r('01', 'garantia', 'total', true)['ruta']);
        $this->assertSame('bloqueado', $r('01', 'error_fecha', 'total', true)['ruta']);
        $this->assertSame('bloqueado', $r('01', 'error_precio', 'valor')['ruta']);
        $this->assertSame('bloqueado', $r('01', 'error_fecha', 'parcial')['ruta']);
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

    // ---- Evento de Retorno sobre Factura ----------------------------------------------------

    public function test_devolucion_parcial_de_factura_emite_evento_de_retorno_valido(): void
    {
        $sale = $this->ventaConDte('01');
        $camisa = $sale->items->first(); // 2 × 50 neto → 56,50 c/IVA por unidad
        $id = $this->ajuste($sale);

        $res = $this->servicio()->ejecutar($sale, $id, 'garantia', 'parcial', 'return_items',
            [['sale_item_id' => $camisa->id, 'quantity' => 1]], null, '');

        $this->assertSame('evento_retorno', $res['ruta']);
        $ev = $res['dte'];
        $this->assertSame(['18', 'ACEPTADO'], [$ev->tipo_dte, $ev->estado]);
        $origen = Dte::where('sale_id', $sale->id)->where('tipo_dte', '01')->first();
        $this->assertSame($origen->id, (int) $ev->dte_origen_id);
        $this->assertSame('ACEPTADO', $origen->fresh()->estado, 'la factura sigue vigente');

        $json = $ev->json_original;
        $this->assertSame([], (new DteSchemaValidator(new DteSchemaRegistry()))->validar($json, 'retorno'));
        $this->assertSame($origen->codigo_generacion, $json['documentoRelacionado'][0]['codigoGeneracion']);
        $this->assertEquals(56.5, $json['resumen']['totalPagar']);
        $this->assertEquals(6.5, $json['resumen']['totalIva']);
        $this->assertNull($json['documento'], 'factura a consumidor final sin identificar');
        $this->assertSame('evento_retorno', DB::table('sale_adjustments')->find($id)->dte_ruta);
    }

    public function test_tras_un_retorno_la_devolucion_del_resto_tambien_es_retorno_y_no_se_invalida(): void
    {
        $sale = $this->ventaConDte('01');
        $camisa = $sale->items->first();
        $id1 = $this->ajuste($sale);
        $this->servicio()->ejecutar($sale, $id1, 'devolucion', 'parcial', 'return_items',
            [['sale_item_id' => $camisa->id, 'quantity' => 1]], null, '');
        DB::table('sale_adjustment_items')->insert(['sale_adjustment_id' => $id1, 'sale_item_id' => $camisa->id, 'product_id' => $camisa->product_id,
            'quantity' => 1, 'product_condition' => 'unopened', 'restocked' => true, 'created_at' => now(), 'updated_at' => now()]);

        // Devolver "todo" ahora ya no puede ser invalidación (hay un retorno vigente): se retorna lo que resta.
        $res = $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'void_invoice'), 'devolucion', 'total', 'void_invoice', [], null, '');
        $this->assertSame('evento_retorno', $res['ruta']);
        // Resta 1 camisa (56,50) + 1 cinturón (56,50) = 113,00
        $this->assertEquals(113.0, $res['dte']->json_original['resumen']['totalPagar']);
        $this->assertSame(2, Dte::where('sale_id', $sale->id)->where('tipo_dte', '18')->count());
        $this->assertSame('ACEPTADO', Dte::where('sale_id', $sale->id)->where('tipo_dte', '01')->first()->estado);
    }

    public function test_factura_con_retorno_no_se_invalida_por_error(): void
    {
        $sale = $this->ventaConDte('01');
        $this->servicio()->ejecutar($sale, $this->ajuste($sale), 'devolucion', 'parcial', 'return_items',
            [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]], null, '');
        $nueva = $this->ventaConDte('01');

        $this->expectExceptionMessage('Evento de Retorno vigente');
        $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'void_invoice'), 'error_fecha', 'total', 'void_invoice', [], $nueva->id, '');
    }

    public function test_plazo_del_retorno_tres_meses_o_dos_anios(): void
    {
        $this->assertSame('2027-02-10', PlazoRetorno::limite('2026-11-10', '47112')->format('Y-m-d'));
        $this->assertSame('2028-11-10', PlazoRetorno::limite('2026-11-10', '46482')->format('Y-m-d'));
        $this->assertFalse(PlazoRetorno::vigente('2026-01-10', '47112', CarbonImmutable::parse('2026-11-20')));
        $this->assertTrue(PlazoRetorno::vigente('2026-01-10', '47721', CarbonImmutable::parse('2026-11-20')));
    }

    public function test_retorno_fuera_de_plazo_se_rechaza(): void
    {
        $sale = $this->ventaConDte('01');
        $d = Dte::where('sale_id', $sale->id)->first();
        $j = $d->json_original;
        $j['identificacion']['fecEmi'] = now()->subMonths(5)->format('Y-m-d');
        $d->update(['json_original' => $j]);

        $this->expectExceptionMessage('Venció el plazo para aplicar un Evento de Retorno');
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

    // ---- Nota de Débito sobre CCF -------------------------------------------------------------

    public function test_nota_de_debito_de_ccf_es_valida_y_suma_iva(): void
    {
        $sale = $this->ventaConDte('03');
        $id = $this->ajuste($sale, 'debit_note');

        $nd = $this->servicio()->emitirDebito($sale, $id, 'intereses_mora', [
            ['descripcion' => 'Intereses por mora de octubre', 'monto_neto' => 100.0],
            ['descripcion' => 'Gastos de cobranza exentos', 'monto_neto' => 5.0, 'categoria' => 'exenta'],
        ], 'cliente pagó tarde');

        $this->assertSame(['06', 'ACEPTADO'], [$nd->tipo_dte, $nd->estado]);
        $origen = Dte::where('sale_id', $sale->id)->where('tipo_dte', '03')->first();
        $this->assertSame($origen->id, (int) $nd->dte_origen_id);

        $json = $nd->json_original;
        $this->assertSame([], (new DteSchemaValidator(new DteSchemaRegistry()))->validar($json, '06'));
        $this->assertSame($origen->codigo_generacion, $json['documentoRelacionado'][0]['numeroDocumento']);
        $this->assertEquals(100.0, $json['resumen']['totalGravada']);
        $this->assertEquals(5.0, $json['resumen']['totalExenta']);
        $this->assertEquals(13.0, $json['resumen']['totalIva']);
        $this->assertEquals(118.0, $json['resumen']['totalPagar']);
        $this->assertStringContainsString('Intereses por mora', $json['resumen']['observaciones']);
        $this->assertArrayNotHasKey('codEstable', $json['emisor']);

        $fila = DB::table('sale_adjustments')->find($id);
        $this->assertSame(['intereses_mora', 'nota_debito', $nd->id], [$fila->motivo, $fila->dte_ruta, (int) $fila->dte_id]);
    }

    public function test_la_nota_de_debito_aumenta_el_saldo_de_una_venta_a_credito(): void
    {
        $sale = $this->ventaConDte('03');
        DB::table('sales')->where('id', $sale->id)->update(['payment_method' => 'credit', 'saldo_pendiente' => 50]);

        $this->servicio()->emitirDebito($sale->fresh(), $this->ajuste($sale, 'debit_note'), 'cargo_adicional',
            [['descripcion' => 'Flete', 'monto_neto' => 10.0]], '');

        $this->assertEquals(61.3, (float) DB::table('sales')->where('id', $sale->id)->value('saldo_pendiente'));
    }

    public function test_la_nota_de_debito_no_toca_el_saldo_si_fue_de_contado(): void
    {
        $sale = $this->ventaConDte('03');
        $this->servicio()->emitirDebito($sale, $this->ajuste($sale, 'debit_note'), 'cargo_adicional',
            [['descripcion' => 'Flete', 'monto_neto' => 10.0]], '');

        $this->assertEquals(0.0, (float) DB::table('sales')->where('id', $sale->id)->value('saldo_pendiente'));
    }

    public function test_no_hay_nota_de_debito_sobre_factura_ni_con_motivo_inventado(): void
    {
        $factura = $this->ventaConDte('01');
        try {
            $this->servicio()->emitirDebito($factura, $this->ajuste($factura, 'debit_note'), 'cargo_adicional', [['descripcion' => 'Flete', 'monto_neto' => 10.0]], '');
            $this->fail('Una factura no admite nota de débito');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Comprobante de Crédito Fiscal', $e->getMessage());
        }

        $ccf = $this->ventaConDte('03');
        $this->expectExceptionMessage('Motivo de nota de débito no reconocido');
        $this->servicio()->emitirDebito($ccf, $this->ajuste($ccf, 'debit_note'), 'inventado', [['descripcion' => 'x', 'monto_neto' => 1.0]], '');
    }

    public function test_pantalla_y_controlador_de_nota_de_debito(): void
    {
        $sale = $this->ventaConDte('03');
        $this->entrar();

        $this->get(route('sales.show', $sale))->assertOk()->assertSee('Nota de débito', false);
        $this->get(route('sales.debit-note', $sale))->assertOk()->assertSee('Intereses por mora', false);

        // Sin cargos válidos: error y nada se guarda.
        $this->post(route('sales.debit-note.store', $sale), ['motivo' => 'otro_cargo', 'lines' => [['descripcion' => 'Sin monto', 'monto' => '']]])
            ->assertSessionHasErrors();
        $this->assertSame(0, DB::table('sale_adjustments')->count());

        $this->post(route('sales.debit-note.store', $sale), [
            'motivo' => 'cargo_adicional', 'lines' => [['descripcion' => 'Flete a domicilio', 'monto' => 20, 'categoria' => 'gravada'], ['descripcion' => '', 'monto' => '']],
        ])->assertSessionHasNoErrors()->assertRedirect(route('sales.show', $sale));

        $this->assertSame(1, Dte::where('sale_id', $sale->id)->where('tipo_dte', '06')->count());
        $this->assertSame('debit_note', DB::table('sale_adjustments')->value('action_type'));
        $this->assertSame('completed', $sale->fresh()->status);

        $this->get(route('sales.show', $sale))->assertSee('Nota de Débito emitida', false);
    }

    public function test_la_pantalla_de_nota_de_debito_no_aplica_a_facturas(): void
    {
        $sale = $this->ventaConDte('01');
        $this->entrar();

        $this->get(route('sales.show', $sale))->assertOk()->assertDontSee('Nota de débito', false);
        $this->get(route('sales.debit-note', $sale))->assertRedirect(route('sales.show', $sale));
    }

    // ---- Invalidación de notas y eventos ------------------------------------------------------

    private function notaCredito(Sale $sale): Dte
    {
        return $this->servicio()->ejecutar($sale, $this->ajuste($sale), 'devolucion', 'parcial', 'return_items',
            [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]], null, '')['dte'];
    }

    private function notaDebito(Sale $sale, float $monto = 10.0): Dte
    {
        return $this->servicio()->emitirDebito($sale, $this->ajuste($sale, 'debit_note'), 'cargo_adicional',
            [['descripcion' => 'Flete', 'monto_neto' => $monto]], '');
    }

    public function test_invalida_una_nota_de_credito_con_evento_valido_y_sin_reemplazo(): void
    {
        $sale = $this->ventaConDte('03');
        $nc = $this->notaCredito($sale);

        $this->servicio()->invalidarDocumento($nc, '1', 'Monto mal digitado');

        $this->assertSame('INVALIDADO', $nc->fresh()->estado);
        $inv = DB::table('dte_invalidaciones')->where('dte_id', $nc->id)->first();
        $evento = json_decode($inv->json_evento, true);
        $this->assertSame([], (new DteSchemaValidator(new DteSchemaRegistry()))->validar($evento, 'invalidacion'));
        $this->assertSame('05', $evento['documento']['tipoDte']);
        $this->assertNull($evento['documento']['codigoGeneracionR']);
        $this->assertSame(1, $evento['motivo']['tipoAnulacion']);
        // Nota de Crédito: la fecha del evento es la de generación de la propia nota.
        $this->assertSame($nc->json_original['identificacion']['fecEmi'], $evento['identificacion']['fecEmi']);
    }

    public function test_tras_invalidar_la_nota_el_ccf_ya_puede_invalidarse(): void
    {
        $sale = $this->ventaConDte('03');
        $nc = $this->notaCredito($sale);
        $nueva = $this->ventaConDte('03');

        try {
            $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'cancel_sale'), 'error_datos_cliente', 'total', 'cancel_sale', [], $nueva->id, '');
            $this->fail('El CCF con nota vigente no debe invalidarse');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('notas de crédito/débito vigentes', $e->getMessage());
        }

        $this->servicio()->invalidarDocumento($nc, '2', 'Anulación de la nota');
        $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'cancel_sale'), 'error_datos_cliente', 'total', 'cancel_sale', [], $nueva->id, '');
        $this->assertSame('INVALIDADO', Dte::where('sale_id', $sale->id)->where('tipo_dte', '03')->first()->estado);
    }

    public function test_invalidar_el_evento_de_retorno_libera_a_la_factura(): void
    {
        $sale = $this->ventaConDte('01');
        $er = $this->servicio()->ejecutar($sale, $this->ajuste($sale), 'devolucion', 'parcial', 'return_items',
            [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1]], null, '')['dte'];
        $nueva = $this->ventaConDte('01');

        try {
            $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'void_invoice'), 'error_fecha', 'total', 'void_invoice', [], $nueva->id, '');
            $this->fail('La factura con retorno vigente no debe invalidarse');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Evento de Retorno', $e->getMessage());
        }

        $this->servicio()->invalidarDocumento($er, '2', 'Retorno registrado por error');
        $evento = json_decode(DB::table('dte_invalidaciones')->where('dte_id', $er->id)->value('json_evento'), true);
        $this->assertSame([], (new DteSchemaValidator(new DteSchemaRegistry()))->validar($evento, 'invalidacion'));
        $this->assertSame('18', $evento['documento']['tipoDte']);
        $this->assertNull($evento['documento']['numeroControl']);

        $this->servicio()->ejecutar($sale, $this->ajuste($sale, 'void_invoice'), 'error_fecha', 'total', 'void_invoice', [], $nueva->id, '');
        $this->assertSame('INVALIDADO', Dte::where('sale_id', $sale->id)->where('tipo_dte', '01')->first()->estado);
    }

    public function test_nota_de_debito_con_error_exige_la_nd_que_la_reemplaza(): void
    {
        $sale = $this->ventaConDte('03');
        $mala = $this->notaDebito($sale, 10.0);

        try {
            $this->servicio()->invalidarDocumento($mala, '1', 'Monto errado');
            $this->fail('Debió exigir el reemplazo');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Nota de Débito correcta', $e->getMessage());
        }
        $this->assertSame('ACEPTADO', $mala->fresh()->estado);

        $buena = $this->notaDebito($sale, 12.0);
        $this->servicio()->invalidarDocumento($mala, '1', 'Monto errado', $buena->id);

        $this->assertSame('INVALIDADO', $mala->fresh()->estado);
        $this->assertSame('ACEPTADO', $buena->fresh()->estado);
        $evento = json_decode(DB::table('dte_invalidaciones')->where('dte_id', $mala->id)->value('json_evento'), true);
        $this->assertSame([], (new DteSchemaValidator(new DteSchemaRegistry()))->validar($evento, 'invalidacion'));
        $this->assertSame($buena->codigo_generacion, $evento['documento']['codigoGeneracionR']);
    }

    public function test_nota_de_debito_rescindida_no_lleva_reemplazo_y_devuelve_el_saldo(): void
    {
        $sale = $this->ventaConDte('03');
        DB::table('sales')->where('id', $sale->id)->update(['payment_method' => 'credit', 'saldo_pendiente' => 50]);
        $nd = $this->notaDebito($sale->fresh(), 10.0); // +11,30
        $this->assertEquals(61.3, (float) DB::table('sales')->where('id', $sale->id)->value('saldo_pendiente'));

        $this->servicio()->invalidarDocumento($nd, '2', 'Cargo improcedente');

        $this->assertEquals(50.0, (float) DB::table('sales')->where('id', $sale->id)->value('saldo_pendiente'));
        $this->assertSame('INVALIDADO', $nd->fresh()->estado);
    }

    public function test_solo_notas_y_retornos_aceptados_dentro_de_plazo(): void
    {
        $sale = $this->ventaConDte('03');
        $ccf = Dte::where('sale_id', $sale->id)->where('tipo_dte', '03')->first();
        try {
            $this->servicio()->invalidarDocumento($ccf, '2', 'x');
            $this->fail('El CCF no se invalida desde aquí');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Solo se pueden invalidar aquí', $e->getMessage());
        }

        $nc = $this->notaCredito($sale);
        try {
            $this->servicio()->invalidarDocumento($nc, '9', 'x');
            $this->fail('Tipo inválido');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Tipo de invalidación', $e->getMessage());
        }
        try {
            $this->servicio()->invalidarDocumento($nc, '2', '  ');
            $this->fail('Motivo vacío');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('motivo', strtolower($e->getMessage()));
        }

        Dte::where('id', $nc->id)->update(['fecha_envio' => now()->subMonths(3)]);
        $this->expectExceptionMessage('Venció el plazo');
        $this->servicio()->invalidarDocumento($nc->fresh(), '2', 'tarde');
    }

    public function test_en_modo_real_se_exige_el_responsable_de_eventos(): void
    {
        $sale = $this->ventaConDte('03');
        $nc = $this->notaCredito($sale);
        Dte::where('id', $nc->id)->update(['sello_recepcion' => str_repeat('B', 40)]);
        DB::table('dte_settings')->where('company_id', $this->company->id)->update(['integration_mode' => 'real']);

        $this->expectExceptionMessage('Configure el responsable de eventos');
        $this->servicio()->invalidarDocumento($nc->fresh(), '2', 'x');
    }

    public function test_el_responsable_configurado_va_en_el_evento(): void
    {
        $sale = $this->ventaConDte('03');
        DB::table('dte_settings')->where('company_id', $this->company->id)->update([
            'responsable_nombre' => 'ANA LOPEZ', 'responsable_tipo_doc' => '13', 'responsable_num_doc' => '012345678',
        ]);
        $nc = $this->notaCredito($sale);
        $this->servicio()->invalidarDocumento($nc, '2', 'x');

        $evento = json_decode(DB::table('dte_invalidaciones')->where('dte_id', $nc->id)->value('json_evento'), true);
        $this->assertSame('ANA LOPEZ', $evento['motivo']['nombreResponsable']);
        $this->assertSame('012345678', $evento['motivo']['numDocSolicita']);
    }

    public function test_pantalla_y_controlador_de_invalidacion(): void
    {
        $sale = $this->ventaConDte('03');
        $nc = $this->notaCredito($sale);
        $this->entrar();

        $this->get(route('sales.show', $sale))->assertOk()->assertSee('Invalidar documento', false);
        $this->get(route('sales.dte.invalidate', [$sale, $nc->id]))->assertOk()->assertSee('Rescindir la operación', false);

        $this->post(route('sales.dte.invalidate.store', [$sale, $nc->id]), ['tipo_invalidacion' => '2', 'motivo' => ''])->assertSessionHasErrors('motivo');
        $this->assertSame('ACEPTADO', $nc->fresh()->estado);

        $this->post(route('sales.dte.invalidate.store', [$sale, $nc->id]), ['tipo_invalidacion' => '2', 'motivo' => 'Se emitió por error'])
            ->assertSessionHasNoErrors()->assertRedirect(route('sales.show', $sale));
        $this->assertSame('INVALIDADO', $nc->fresh()->estado);

        $this->get(route('sales.show', $sale))->assertSee('INVALIDADO', false)->assertDontSee('Invalidar documento', false);
        $this->get(route('sales.dte.invalidate', [$sale, $nc->id]))->assertRedirect(route('sales.show', $sale));
    }

    public function test_no_se_invalida_un_documento_de_otra_venta(): void
    {
        $a = $this->ventaConDte('03');
        $b = $this->ventaConDte('03');
        $ncB = $this->notaCredito($b);
        $this->entrar();

        $this->get(route('sales.dte.invalidate', [$a, $ncB->id]))->assertNotFound();
        $this->post(route('sales.dte.invalidate.store', [$a, $ncB->id]), ['tipo_invalidacion' => '2', 'motivo' => 'x'])->assertNotFound();
        $this->assertSame('ACEPTADO', $ncB->fresh()->estado);
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

    public function test_el_controlador_revierte_todo_si_la_ruta_esta_bloqueada(): void
    {
        $sale = $this->ventaConDte('01');
        $this->entrar();

        // Un error de fecha en una factura solo se corrige invalidándola COMPLETA: con devolución parcial se bloquea.
        $this->post(route('sales.admin-adjustment.store', $sale), [
            'action_type' => 'return_items', 'motivo' => 'error_fecha', 'return_products' => 1,
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1, 'condition' => 'unopened']],
        ])->assertSessionHasErrors();

        $this->assertSame(0, DB::table('sale_adjustments')->count());
        $this->assertSame(0, DB::table('inventory_movements')->count());
        $this->assertSame('completed', $sale->fresh()->status);
    }

    public function test_el_controlador_devuelve_parcialmente_una_factura_con_evento_de_retorno(): void
    {
        $sale = $this->ventaConDte('01');
        $this->entrar();

        $this->post(route('sales.admin-adjustment.store', $sale), [
            'action_type' => 'return_items', 'motivo' => 'cambio_producto', 'return_products' => 1, 'refund_cash' => 1, 'refund_amount' => 56.5,
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1, 'condition' => 'unopened']],
        ])->assertSessionHasNoErrors();

        $this->assertSame('completed', $sale->fresh()->status);
        $this->assertSame(1, Dte::where('sale_id', $sale->id)->where('tipo_dte', '18')->count());
        $this->assertSame(1, DB::table('inventory_movements')->count(), 'sin abrir vuelve a inventario');
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
