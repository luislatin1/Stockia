<?php

namespace Tests\Feature\Dte;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Stockia\DteSvMh\Builders\ContingenciaBuilder;
use Stockia\DteSvMh\Models\Dte;
use Stockia\DteSvMh\Models\DteEstablecimiento;
use Stockia\DteSvMh\Schemas\DteSchemaRegistry;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;
use Stockia\DteSvMh\Services\DteEmissionService;
use Tests\TestCase;

/** Evento de Contingencia y ciclo de tres momentos (Manual Funcional v2.0 §VIII). */
class ContingenciaTest extends TestCase
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
        $cu = DB::table('company_user')->insertGetId(['company_id' => $this->company->id, 'user_id' => $this->user->id, 'role' => 'SuperAdmin', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('company_user_warehouse')->insert(['company_user_id' => $cu, 'warehouse_id' => $this->warehouse->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function modo(string $modo, array $extra = []): void
    {
        DB::table('dte_settings')->updateOrInsert(['company_id' => $this->company->id], $extra + [
            'enabled' => true, 'integration_mode' => $modo, 'ambiente' => '00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('dte_settings')->where('company_id', $this->company->id)->update(['integration_mode' => $modo] + $extra);
    }

    private function factura(): Dte
    {
        $p = Product::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'name' => 'Prod '.uniqid(), 'price' => 100, 'stock' => 5]);
        $sale = new Sale();
        $sale->forceFill([
            'company_id' => $this->company->id, 'warehouse_id' => $this->warehouse->id, 'document_type' => 'factura', 'tipo_dte' => '01', 'status' => 'completed',
            'subtotal' => 100, 'gravadas' => 100, 'iva' => 13, 'tax_total' => 13, 'total' => 113, 'payment_method' => 'cash', 'numero_interno' => 'V-'.uniqid(),
        ])->save();
        $i = new SaleItem();
        $i->forceFill(['sale_id' => $sale->id, 'product_id' => $p->id, 'quantity' => 1, 'price' => 100, 'precio_unitario' => 100, 'monto_gravado' => 100, 'iva_item' => 13, 'total_item' => 113, 'subtotal' => 100, 'tipo_item' => 1, 'uni_medida' => 59])->save();

        return app(DteEmissionService::class)->emitForSale($sale->fresh());
    }

    private function validar(array $evento): array
    {
        return (new DteSchemaValidator(new DteSchemaRegistry()))->validar($evento, 'contingencia');
    }

    // ------------------------------------------------------------- constructor

    private function ctx(array $extra = []): array
    {
        return array_merge([
            'ambiente' => '00', 'codigo_generacion' => 'f1f2f3f4-e5f6-4789-9abc-def012345678',
            'fecha' => Carbon::parse('2026-11-20 16:00:00', 'America/El_Salvador'),
            'inicio' => Carbon::parse('2026-11-20 09:10:00', 'America/El_Salvador'), 'fin' => Carbon::parse('2026-11-20 15:30:00', 'America/El_Salvador'),
            'tipo_contingencia' => 3, 'motivo' => null,
            'emisor' => [
                'nit' => '05122808871013', 'nombre' => 'EMPRESA SA', 'responsable' => ['nombre' => 'ANA LOPEZ', 'tipo_doc' => '13', 'num_doc' => '012345678'],
                'tipo_establecimiento' => '02', 'codEstableMH' => 'M001', 'codPuntoVentaMH' => 'P001', 'telefono' => '22223333', 'correo' => 'v@x.com.sv',
            ],
            'documentos' => [['tipo_dte' => '01', 'codigo_generacion' => 'a1b2c3d4-e5f6-4789-9abc-def012345678'], ['tipo_dte' => '03', 'codigo_generacion' => 'b1b2c3d4-e5f6-4789-9abc-def012345678']],
        ], $extra);
    }

    public function test_el_evento_cumple_el_esquema_oficial(): void
    {
        $e = (new ContingenciaBuilder())->build($this->ctx());

        $this->assertSame([], $this->validar($e));
        $this->assertSame(4, $e['identificacion']['version']);
        $this->assertSame([1, 2], array_column($e['detalleDTE'], 'noItem'));
        $this->assertSame('09:10:00', $e['motivo']['hInicio']);
        $this->assertSame('ANA LOPEZ', $e['emisor']['nombreResponsable']);
        $this->assertNull($e['motivo']['motivoContingencia']);
    }

    public function test_el_motivo_es_obligatorio_solo_con_el_tipo_cinco_y_se_puede_ampliar_en_los_demas(): void
    {
        $this->assertSame([], $this->validar((new ContingenciaBuilder())->build($this->ctx(['tipo_contingencia' => 5, 'motivo' => 'Falla del proveedor']))));
        $e = (new ContingenciaBuilder())->build($this->ctx(['tipo_contingencia' => 4, 'motivo' => 'Corte de luz en la zona']));
        $this->assertSame('Corte de luz en la zona', $e['motivo']['motivoContingencia']);

        $this->expectExceptionMessage('obligatorio explicar el motivo');
        (new ContingenciaBuilder())->build($this->ctx(['tipo_contingencia' => 5, 'motivo' => '']));
    }

    public function test_limites_de_documentos_y_fechas(): void
    {
        $mil = array_map(fn ($i) => ['tipo_dte' => '01', 'codigo_generacion' => sprintf('A1B2C3D4-E5F6-4789-9ABC-%012d', $i)], range(1, 1000));
        $this->assertSame([], $this->validar((new ContingenciaBuilder())->build($this->ctx(['documentos' => $mil]))));

        $casos = [
            [['documentos' => []], 'al menos un documento'],
            [['documentos' => array_merge($mil, [['tipo_dte' => '01', 'codigo_generacion' => 'A1B2C3D4-E5F6-4789-9ABC-000000009999']])], 'máximo 1000'],
            [['tipo_contingencia' => 9], 'Tipo de contingencia'],
            [['inicio' => Carbon::parse('2026-11-21 00:00:00', 'America/El_Salvador')], 'terminar antes de empezar'],
        ];
        foreach ($casos as [$extra, $mensaje]) {
            try {
                (new ContingenciaBuilder())->build($this->ctx($extra));
                $this->fail("Debió fallar: {$mensaje}");
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString($mensaje, $e->getMessage());
            }
        }
    }

    // ------------------------------------------------------------- ciclo

    public function test_los_documentos_en_contingencia_se_agrupan_por_establecimiento_sin_pisar_la_fecha_de_inicio(): void
    {
        $this->modo('contingencia');
        $a = $this->factura();
        $inicio = DB::table('dte_contingencias')->value('fecha_inicio');

        $this->travel(2)->hours();
        $b = $this->factura();

        $this->assertSame('CONTINGENCIA', $a->estado);
        $this->assertSame(1, DB::table('dte_contingencias')->count(), 'una sola contingencia abierta por establecimiento');
        $c = DB::table('dte_contingencias')->first();
        $this->assertSame(['M001', 'ABIERTA'], [$c->establecimiento, $c->estado]);
        $this->assertSame($inicio, $c->fecha_inicio, 'el segundo documento no reinicia el inicio de la contingencia');
        $this->assertSame(2, DB::table('dte_contingencia_dtes')->where('contingencia_id', $c->id)->count());
        $this->assertSame(1, $a->json_original['identificacion']['tipoContingencia']);
        $this->assertSame(2, $b->json_original['identificacion']['tipoOperacion']);
    }

    public function test_no_se_transmite_el_evento_mientras_se_opera_en_contingencia(): void
    {
        $this->modo('contingencia');
        $this->factura();
        $id = DB::table('dte_contingencias')->value('id');

        $this->expectExceptionMessage('Cambie el modo de integración');
        app(DteEmissionService::class)->cerrarContingencia($id);
    }

    public function test_ciclo_completo_evento_con_sello_y_luego_lote(): void
    {
        $this->modo('contingencia');
        $a = $this->factura();
        $b = $this->factura();
        $id = DB::table('dte_contingencias')->value('id');
        $svc = app(DteEmissionService::class);

        // El lote no puede ir antes del evento.
        $this->modo('simulacion');
        try {
            $svc->enviarLoteContingencia($id);
            $this->fail('El lote requiere el evento con sello');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Primero debe transmitirse el evento', $e->getMessage());
        }
        $this->assertSame('CONTINGENCIA', $a->fresh()->estado);

        // Momento 2: el evento.
        $r = $svc->cerrarContingencia($id);
        $this->assertSame('EVENTO_ENVIADO', $r['estado']);
        $this->assertNotEmpty($r['sello']);
        $c = DB::table('dte_contingencias')->find($id);
        $evento = json_decode($c->json_evento, true);
        $this->assertSame([], $this->validar($evento));
        $this->assertEqualsCanonicalizing([$a->codigo_generacion, $b->codigo_generacion], array_column($evento['detalleDTE'], 'codigoGeneracion'));
        $this->assertSame(1, $evento['motivo']['tipoContingencia']);
        $this->assertSame('M001', $evento['emisor']['codEstableMH']);
        $this->assertSame('P001', $evento['emisor']['codPuntoVentaMH']);
        $this->assertNotNull($c->lote_limite_at);
        $this->assertEqualsWithDelta(72 * 3600, abs(\Carbon\Carbon::parse($c->lote_limite_at, 'UTC')->diffInSeconds(\Carbon\Carbon::parse($c->evento_enviado_at, 'UTC'))), 5);
        $this->assertSame('CONTINGENCIA', $a->fresh()->estado, 'los documentos siguen pendientes hasta el lote');

        // El evento no se transmite dos veces.
        try {
            $svc->cerrarContingencia($id);
            $this->fail('No debe repetirse el evento');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('EVENTO_ENVIADO', $e->getMessage());
        }

        // Momento 3: el lote.
        $this->assertSame(2, $svc->enviarLoteContingencia($id));
        $this->assertSame(['ACEPTADO', 'ACEPTADO'], [$a->fresh()->estado, $b->fresh()->estado]);
        $this->assertNotNull($a->fresh()->sello_recepcion);
        $this->assertSame('CERRADA', DB::table('dte_contingencias')->find($id)->estado);
        $this->assertSame(2, DB::table('dte_contingencia_dtes')->where('reenviado', true)->count());
    }

    public function test_el_lote_vencido_a_las_72_horas_se_rechaza(): void
    {
        $this->modo('contingencia');
        $this->factura();
        $id = DB::table('dte_contingencias')->value('id');
        $this->modo('simulacion');
        app(DteEmissionService::class)->cerrarContingencia($id);

        $this->travel(73)->hours();
        $this->expectExceptionMessage('Venció el plazo de 72 horas');
        app(DteEmissionService::class)->enviarLoteContingencia($id);
    }

    public function test_un_evento_rechazado_se_puede_reintentar(): void
    {
        $this->modo('contingencia');
        $dte = $this->factura();
        $id = DB::table('dte_contingencias')->value('id');
        $svc = app(DteEmissionService::class);

        $this->modo('simulacion', ['static_estado' => 'RECHAZADO']);
        $r = $svc->cerrarContingencia($id);
        $this->assertSame('EVENTO_RECHAZADO', $r['estado']);
        $this->assertNull(DB::table('dte_contingencias')->find($id)->sello_evento);
        try {
            $svc->enviarLoteContingencia($id);
            $this->fail('Sin sello no hay lote');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('sello', $e->getMessage());
        }

        $this->modo('simulacion', ['static_estado' => 'ACEPTADO']);
        $this->assertSame('EVENTO_ENVIADO', $svc->cerrarContingencia($id)['estado']);
        $this->assertSame(1, $svc->enviarLoteContingencia($id));
        $this->assertSame('ACEPTADO', $dte->fresh()->estado);
    }

    public function test_el_orquestador_hace_evento_y_lote_y_avisa_lo_que_no_pudo(): void
    {
        $this->modo('contingencia');
        $a = $this->factura();
        $svc = app(DteEmissionService::class);

        $this->assertSame(0, $svc->resendContingency($this->company->id));
        $this->assertStringContainsString('Cambie el modo de integración', $svc->avisosContingencia()[0]);

        $this->modo('simulacion');
        $this->assertSame(1, $svc->resendContingency($this->company->id));
        $this->assertSame([], $svc->avisosContingencia());
        $this->assertSame('ACEPTADO', $a->fresh()->estado);
        $this->assertSame(0, $svc->resendContingency($this->company->id), 'nada pendiente');
    }

    public function test_el_responsable_es_obligatorio_en_modo_real_y_se_usa_el_configurado(): void
    {
        $this->modo('contingencia');
        $this->factura();
        $id = DB::table('dte_contingencias')->value('id');

        $this->modo('simulacion', ['responsable_nombre' => 'ANA LOPEZ', 'responsable_tipo_doc' => '13', 'responsable_num_doc' => '012345678']);
        app(DteEmissionService::class)->cerrarContingencia($id);
        $evento = json_decode(DB::table('dte_contingencias')->find($id)->json_evento, true);
        $this->assertSame(['ANA LOPEZ', '012345678'], [$evento['emisor']['nombreResponsable'], $evento['emisor']['numeroDocResponsable']]);

        // En real se exige: contingencia nueva sin responsable configurado.
        $this->modo('contingencia', ['responsable_nombre' => null, 'responsable_tipo_doc' => null, 'responsable_num_doc' => null]);
        $this->factura();
        $id2 = DB::table('dte_contingencias')->where('estado', 'ABIERTA')->value('id');
        $this->modo('real');
        $this->expectExceptionMessage('Configure el responsable');
        app(DteEmissionService::class)->cerrarContingencia($id2);
    }

    // ------------------------------------------------------------- pantalla

    private function entrar(): void
    {
        $this->actingAs($this->user)->withSession(['current_company_id' => $this->company->id, 'current_warehouse_id' => $this->warehouse->id]);
    }

    public function test_el_panel_muestra_las_contingencias_y_permite_evento_y_lote(): void
    {
        $this->modo('contingencia');
        $dte = $this->factura();
        $id = DB::table('dte_contingencias')->value('id');
        $this->modo('simulacion');
        $this->entrar();

        $this->get(route('dte.admin.index'))->assertOk()->assertSee('Transmitir evento', false)->assertSee('M001', false);

        $this->post(route('dte.contingencias.lote', $id))->assertSessionHasErrors();
        $this->post(route('dte.contingencias.evento', $id))->assertSessionHasNoErrors();
        $this->get(route('dte.admin.index'))->assertSee('Transmitir lote', false)->assertSee('EVENTO_ENVIADO', false);

        $this->post(route('dte.contingencias.lote', $id))->assertSessionHasNoErrors();
        $this->assertSame('ACEPTADO', $dte->fresh()->estado);
        $this->get(route('dte.admin.index'))->assertSee('CERRADA', false)->assertDontSee('Transmitir lote', false);
    }

    public function test_no_se_toca_la_contingencia_de_otra_empresa(): void
    {
        $this->modo('contingencia');
        $this->factura();
        $id = DB::table('dte_contingencias')->value('id');
        $otra = Company::create(['name' => 'Otra', 'currency_id' => $this->company->currency_id]);
        $cuOtra = DB::table('company_user')->insertGetId(['company_id' => $otra->id, 'user_id' => $this->user->id, 'role' => 'SuperAdmin', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('company_user_warehouse')->insert(['company_user_id' => $cuOtra, 'warehouse_id' => $this->warehouse->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->user)->withSession(['current_company_id' => $otra->id, 'current_warehouse_id' => $this->warehouse->id]);

        $this->post(route('dte.contingencias.evento', $id))->assertNotFound();
    }
}
