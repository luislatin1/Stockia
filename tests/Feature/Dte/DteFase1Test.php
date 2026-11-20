<?php

namespace Tests\Feature\Dte;

use App\Models\Company;
use App\Models\Currency;
use App\Services\Dte\DteCalculationService;
use App\Services\Dte\TaxRateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Stockia\DteSvMh\Models\DteEstablecimiento;
use Stockia\DteSvMh\Models\DtePuntoVenta;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\DteNumberingService;
use Tests\TestCase;

/**
 * Fase 1 DTE 2.0: número de control, tasas con vigencia, secretos cifrados
 * y catálogo de distritos.
 */
class DteFase1Test extends TestCase
{
    use RefreshDatabase;

    private function company(string $name = 'Empresa'): Company
    {
        $currency = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dolar', 'symbol' => '$']);

        return Company::create(['name' => $name, 'currency_id' => $currency->id]);
    }

    /** @return array{0: DteEstablecimiento, 1: DtePuntoVenta} */
    private function emisor(Company $company, string $estab = 'M001', string $pv = 'P001'): array
    {
        $e = DteEstablecimiento::create([
            'company_id' => $company->id, 'codigo' => $estab, 'tipo_establecimiento' => '02', 'nombre' => 'Matriz',
        ]);
        $p = $e->puntosVenta()->create(['codigo' => $pv, 'nombre' => 'Caja']);

        return [$e, $p];
    }

    public function test_numero_de_control_tiene_formato_oficial_de_31_caracteres(): void
    {
        $c = $this->company();
        [$e, $p] = $this->emisor($c);

        $n = app(DteNumberingService::class)->next($c->id, $e, $p, '01');

        $this->assertSame('DTE-01-M001P001-000000000000001', $n['numero_control']);
        $this->assertSame(31, strlen($n['numero_control']));
        $this->assertMatchesRegularExpression('/^DTE-01-(M|B|S|P)([0-9]{3})(P)([0-9]{3})-[0-9]{15}$/', $n['numero_control']);
    }

    public function test_el_consecutivo_no_depende_del_tipo_ni_del_punto_de_venta(): void
    {
        $c = $this->company();
        [$e, $p1] = $this->emisor($c);
        $p2 = $e->puntosVenta()->create(['codigo' => 'P002', 'nombre' => 'Caja 2']);
        $svc = app(DteNumberingService::class);

        $a = $svc->next($c->id, $e, $p1, '01');
        $b = $svc->next($c->id, $e, $p1, '03');   // otro tipo
        $d = $svc->next($c->id, $e, $p2, '01');   // otro punto de venta

        $this->assertSame([1, 2, 3], [$a['consecutivo'], $b['consecutivo'], $d['consecutivo']]);
        $this->assertSame('DTE-03-M001P001-000000000000002', $b['numero_control']);
        $this->assertSame('DTE-01-M001P002-000000000000003', $d['numero_control']);
    }

    public function test_modo_centralizado_comparte_consecutivo_entre_establecimientos(): void
    {
        $c = $this->company();
        [$e1, $p1] = $this->emisor($c, 'M001', 'P001');
        [$e2, $p2] = $this->emisor($c, 'S001', 'P001');
        $svc = app(DteNumberingService::class);

        $svc->next($c->id, $e1, $p1, '01', DteNumberingService::MODO_CENTRALIZADA);
        $n = $svc->next($c->id, $e2, $p2, '01', DteNumberingService::MODO_CENTRALIZADA);

        $this->assertSame(2, $n['consecutivo']);
    }

    public function test_modo_por_establecimiento_lleva_consecutivos_independientes(): void
    {
        $c = $this->company();
        [$e1, $p1] = $this->emisor($c, 'M001', 'P001');
        [$e2, $p2] = $this->emisor($c, 'S001', 'P001');
        $svc = app(DteNumberingService::class);
        $modo = DteNumberingService::MODO_POR_ESTABLECIMIENTO;

        $svc->next($c->id, $e1, $p1, '01', $modo);
        $svc->next($c->id, $e1, $p1, '01', $modo);
        $n = $svc->next($c->id, $e2, $p2, '01', $modo);

        $this->assertSame(1, $n['consecutivo']);
        $this->assertSame('DTE-01-S001P001-000000000000001', $n['numero_control']);
    }

    public function test_el_consecutivo_se_reinicia_cada_ejercicio_en_hora_de_el_salvador(): void
    {
        $c = $this->company();
        [$e, $p] = $this->emisor($c);
        $svc = app(DteNumberingService::class);

        // 31-dic 23:00 en El Salvador (UTC-6) = 1-ene 05:00 UTC: sigue siendo el ejercicio anterior.
        $finDeAnio = Carbon::parse('2027-01-01 05:00:00', 'UTC');
        $a = $svc->next($c->id, $e, $p, '01', DteNumberingService::MODO_CENTRALIZADA, $finDeAnio);
        $b = $svc->next($c->id, $e, $p, '01', DteNumberingService::MODO_CENTRALIZADA, $finDeAnio);

        $this->assertSame(2026, $a['ejercicio']);
        $this->assertSame(2, $b['consecutivo']);

        // Ya es 1-ene en El Salvador: ejercicio nuevo, reinicia en 1.
        $nuevo = Carbon::parse('2027-01-01 06:00:00', 'UTC');
        $c1 = $svc->next($c->id, $e, $p, '01', DteNumberingService::MODO_CENTRALIZADA, $nuevo);

        $this->assertSame(2027, $c1['ejercicio']);
        $this->assertSame(1, $c1['consecutivo']);
        $this->assertSame('DTE-01-M001P001-000000000000001', $c1['numero_control']);
    }

    public function test_rechaza_codigos_con_formato_invalido(): void
    {
        $c = $this->company();
        [$e, $p] = $this->emisor($c, '0001', 'P001'); // formato viejo: sin letra

        $this->expectException(\InvalidArgumentException::class);
        app(DteNumberingService::class)->next($c->id, $e, $p, '01');
    }

    public function test_rechaza_un_punto_de_venta_de_otro_establecimiento(): void
    {
        $c = $this->company();
        [$e1] = $this->emisor($c, 'M001', 'P001');
        [, $pDeOtro] = $this->emisor($c, 'S001', 'P001');

        $this->expectException(\InvalidArgumentException::class);
        app(DteNumberingService::class)->next($c->id, $e1, $pDeOtro, '01');
    }

    public function test_tasa_de_iva_es_13_y_respeta_la_vigencia(): void
    {
        $svc = new TaxRateService();
        $this->assertEqualsWithDelta(0.13, $svc->rate('IVA', Carbon::parse('2026-11-19')), 0.00001);

        // Un cambio de tasa es una fila nueva, no un cambio de código.
        DB::table('tax_rates')->where('codigo', 'IVA')->update(['vigente_hasta' => '2030-12-31']);
        DB::table('tax_rates')->insert([
            'codigo' => 'IVA', 'nombre' => 'IVA', 'tipo' => 'impuesto', 'tasa' => 15, 'vigente_desde' => '2031-01-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $nuevo = new TaxRateService();
        $sv = 'America/El_Salvador'; // la vigencia se evalúa por día calendario en hora fiscal
        $this->assertEqualsWithDelta(0.13, $nuevo->rate('IVA', Carbon::parse('2030-12-31 23:59:00', $sv)), 0.00001);
        $this->assertEqualsWithDelta(0.15, $nuevo->rate('IVA', Carbon::parse('2031-01-01 00:00:00', $sv)), 0.00001);
        // 1-ene 00:00 UTC todavía es 31-dic en El Salvador.
        $this->assertEqualsWithDelta(0.13, $nuevo->rate('IVA', Carbon::parse('2031-01-01 00:00:00', 'UTC')), 0.00001);
    }

    public function test_el_calculo_usa_la_tasa_vigente_en_la_fecha_de_la_operacion(): void
    {
        DB::table('tax_rates')->where('codigo', 'IVA')->update(['vigente_hasta' => '2030-12-31']);
        DB::table('tax_rates')->insert([
            'codigo' => 'IVA', 'nombre' => 'IVA', 'tipo' => 'impuesto', 'tasa' => 15, 'vigente_desde' => '2031-01-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $item = ['product_id' => 1, 'quantity' => 1, 'unit_price' => 100.00];
        $calc = app(DteCalculationService::class);

        $hoy = $calc->calculate([$item]);
        $futuro = $calc->calculate([$item], ['fecha' => Carbon::parse('2031-06-01')]);

        $this->assertSame(13.0, $hoy['totals']['iva']);
        $this->assertSame(15.0, $futuro['totals']['iva']);
    }

    public function test_las_contrasenas_dte_se_guardan_cifradas_y_no_se_serializan(): void
    {
        $c = $this->company();

        $s = DteSetting::create([
            'company_id' => $c->id,
            'api_password' => 'Secreta#123456',
            'signer_password' => 'Llave#987654',
        ]);

        $raw = DB::table('dte_settings')->where('id', $s->id)->first();
        $this->assertNotSame('Secreta#123456', $raw->api_password);
        $this->assertNotSame('Llave#987654', $raw->signer_password);
        $this->assertStringNotContainsString('Secreta', (string) $raw->api_password);

        $fresh = DteSetting::find($s->id);
        $this->assertSame('Secreta#123456', $fresh->api_password);   // se descifra al usarse
        $this->assertSame('Llave#987654', $fresh->signer_password);
        $this->assertArrayNotHasKey('api_password', $fresh->toArray());
        $this->assertArrayNotHasKey('signer_password', $fresh->toArray());
    }

    public function test_catalogo_de_distritos_trae_262_y_su_version_registrada(): void
    {
        $this->assertSame(262, DB::table('dte_distritos')->count());
        $this->assertSame(14, DB::table('dte_distritos')->distinct()->count('departamento_codigo'));

        $v = DB::table('dte_catalogo_versiones')->where('catalogo', 'CAT-008')->first();
        $this->assertNotNull($v);
        $this->assertSame(262, (int) $v->filas);
        $this->assertSame(64, strlen($v->hash));
    }

    public function test_company_y_customer_aceptan_distrito(): void
    {
        $c = $this->company();
        $c->update(['distrito' => '03']);
        $this->assertSame('03', $c->fresh()->distrito);
    }

    public function test_cada_distrito_conoce_su_municipio_y_la_tripleta_se_valida(): void
    {
        $this->assertSame(0, DB::table('dte_distritos')->whereNull('municipio_codigo')->count());
        // Ahuachapán (01): el distrito 01 pertenece a Ahuachapán Centro (14), no a Norte (13).
        $this->assertTrue(\App\Support\UbicacionCatalogo::distritoValido('01', '14', '01'));
        $this->assertTrue(\App\Support\UbicacionCatalogo::distritoValido('01', '0114', '01'));
        $this->assertFalse(\App\Support\UbicacionCatalogo::distritoValido('01', '13', '01'));
        $this->assertFalse(\App\Support\UbicacionCatalogo::distritoValido('02', '14', '01'));
    }
}
