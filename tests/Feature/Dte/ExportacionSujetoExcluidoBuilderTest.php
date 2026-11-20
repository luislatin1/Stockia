<?php

namespace Tests\Feature\Dte;

use Carbon\Carbon;
use Stockia\DteSvMh\Builders\ExportacionBuilder;
use Stockia\DteSvMh\Builders\SujetoExcluidoBuilder;
use Stockia\DteSvMh\Schemas\DteSchemaRegistry;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;
use Tests\TestCase;

class ExportacionSujetoExcluidoBuilderTest extends TestCase
{
    private function validar(array $dte, string $clave): array
    {
        return (new DteSchemaValidator(new DteSchemaRegistry()))->validar($dte, $clave);
    }

    private function base(string $tipo, array $emisorExtra = []): array
    {
        return [
            'ambiente' => '00', 'numero_control' => "DTE-{$tipo}-M001P001-000000000000005",
            'codigo_generacion' => 'e1e2e3e4-e5f6-4789-9abc-def012345678',
            'fecha' => Carbon::parse('2026-11-20 14:05:09', 'America/El_Salvador'), 'contingencia' => null,
            'emisor' => [
                'nit' => '05122808871013', 'nrc' => '231194', 'nombre' => 'EMPRESA SA', 'codActividad' => '47112',
                'descActividad' => 'Venta al por menor', 'nombreComercial' => null,
                'direccion' => ['departamento' => '06', 'municipio' => '21', 'distrito' => '01', 'complemento' => 'Calle 1'],
                'telefono' => '22223333', 'correo' => 'v@x.com.sv', 'codEstable' => 'M001', 'codPuntoVenta' => 'P001',
            ] + $emisorExtra,
        ];
    }

    private function exportacion(array $emisorExtra = [], array $extra = []): array
    {
        $c = $this->base('11', $emisorExtra + ['tipoItemExpor' => 1, 'recintoFiscal' => '01', 'tipoRegimen' => 'EX-1', 'regimen' => '1000.000']);
        $c['receptor'] = [
            'nombre' => 'ACME INC', 'tipoDocumento' => '03', 'numDocumento' => 'X1234567', 'descActividad' => 'Comercio al por mayor',
            'nombreComercial' => null, 'codPais' => 'US', 'nombrePais' => 'Estados Unidos', 'complemento' => '5th Avenue 100, New York',
            'tipoPersona' => 2, 'telefono' => '+12125550100', 'correo' => 'buyer@acme.com',
        ];
        $c['items'] = [
            ['codigo' => 'P1', 'descripcion' => 'Café tostado', 'cantidad' => 10, 'precio' => 12.5, 'descuento' => 5, 'tipo_item' => 1, 'uni_medida' => 59],
            ['codigo' => 'P2', 'descripcion' => 'Panela', 'cantidad' => 4, 'precio' => 3.333333, 'tipo_item' => 1, 'uni_medida' => 59],
        ];

        return array_merge($c, $extra);
    }

    public function test_exportacion_de_bienes_cumple_el_esquema_y_no_lleva_iva(): void
    {
        $dte = (new ExportacionBuilder())->build($this->exportacion([], ['seguro' => 10, 'flete' => 25, 'cod_incoterms' => '09', 'desc_incoterms' => 'FOB-Libre a bordo']));

        $this->assertSame([], $this->validar($dte, '11'));
        $this->assertSame(3, $dte['identificacion']['version']);
        $this->assertSame(['C3'], $dte['cuerpoDocumento'][0]['tributos']);
        // (10×12,5 − 5) + 4×3,333333 = 120 + 13,333332 = 133,333332 → 133,33
        $this->assertEquals(133.33, $dte['resumen']['totalGravada']);
        $this->assertEquals(0.0, $dte['resumen']['tributos'][0]['valor']);
        $this->assertEquals(168.33, $dte['resumen']['montoTotalOperacion']);
        $this->assertEquals(168.33, $dte['resumen']['totalPagar']);
        $this->assertSame('09', $dte['resumen']['codIncoterms']);
    }

    public function test_exportacion_de_servicios_deja_null_recinto_y_regimen(): void
    {
        $dte = (new ExportacionBuilder())->build($this->exportacion(['tipoItemExpor' => 2, 'recintoFiscal' => '01', 'tipoRegimen' => 'EX-1', 'regimen' => '1000.000']));

        $this->assertSame([], $this->validar($dte, '11'));
        $this->assertNull($dte['emisor']['recintoFiscal']);
        $this->assertNull($dte['emisor']['tipoRegimen']);
        $this->assertNull($dte['emisor']['regimen']);
    }

    public function test_exportacion_de_bienes_exige_recinto_tipo_regimen_y_regimen(): void
    {
        $this->expectExceptionMessage('recinto fiscal, tipo de régimen y régimen');
        (new ExportacionBuilder())->build($this->exportacion(['recintoFiscal' => null]));
    }

    public function test_exportacion_a_credito_lleva_plazo(): void
    {
        $dte = (new ExportacionBuilder())->build($this->exportacion([], ['condicion_operacion' => 2, 'dias_credito' => 45]));
        $this->assertSame([], $this->validar($dte, '11'));
        $this->assertSame(45, (int) $dte['resumen']['pagos'][0]['periodo']);
        $this->assertNull($dte['resumen']['pagos'][0]['codigo']);
    }

    private function sujetoExcluido(array $extra = []): array
    {
        $c = $this->base('14');
        unset($c['emisor']['nombreComercial']);
        $c['receptor'] = [
            'tipoDocumento' => '13', 'numDocumento' => '012345678', 'nombre' => 'MARIA HERNANDEZ', 'codActividad' => null, 'descActividad' => null,
            'direccion' => ['departamento' => '06', 'municipio' => '14', 'distrito' => '01', 'complemento' => 'Col. Zacamil'],
            'telefono' => '77778888', 'correo' => null,
        ];
        $c['items'] = [
            ['codigo' => null, 'descripcion' => 'Hortalizas', 'cantidad' => 20, 'precio' => 2.5, 'descuento' => 0, 'tipo_item' => 1, 'uni_medida' => 59],
            ['codigo' => 'S1', 'descripcion' => 'Flete local', 'cantidad' => 1, 'precio' => 10.005, 'tipo_item' => 2, 'uni_medida' => 99],
        ];

        return array_merge($c, $extra);
    }

    public function test_sujeto_excluido_cumple_el_esquema_y_no_lleva_iva(): void
    {
        $dte = (new SujetoExcluidoBuilder())->build($this->sujetoExcluido());

        $this->assertSame([], $this->validar($dte, '14'));
        $this->assertSame('14', $dte['identificacion']['tipoDte']);
        $this->assertEquals(50.0, $dte['cuerpoDocumento'][0]['compra']);
        // 50 + 10,005 = 60,005 → 60,01 (tercera cifra ≥ 5 sube)
        $this->assertEquals(60.01, $dte['resumen']['totalCompra']);
        $this->assertEquals(60.01, $dte['resumen']['totalPagar']);
        $this->assertEquals(0.0, $dte['resumen']['reteRenta']);
    }

    public function test_sujeto_excluido_con_retencion_de_renta(): void
    {
        $dte = (new SujetoExcluidoBuilder())->build($this->sujetoExcluido(['retencion_renta' => 6.0]));

        $this->assertSame([], $this->validar($dte, '14'));
        $this->assertEquals(54.01, $dte['resumen']['totalPagar']);
        $this->assertEquals(6.0, $dte['resumen']['reteRenta']);
    }

    public function test_la_retencion_no_puede_superar_la_compra(): void
    {
        $this->expectExceptionMessage('retención de renta');
        (new SujetoExcluidoBuilder())->build($this->sujetoExcluido(['retencion_renta' => 500]));
    }

    public function test_sujeto_excluido_a_credito_y_en_contingencia(): void
    {
        $dte = (new SujetoExcluidoBuilder())->build($this->sujetoExcluido(['condicion_operacion' => 2, 'dias_credito' => 15, 'contingencia' => ['tipo' => 3, 'motivo' => 'x']]));
        $this->assertSame([], $this->validar($dte, '14'));
        $this->assertSame(2, $dte['identificacion']['tipoOperacion']);
        $this->assertNull($dte['identificacion']['motivoContin']);
    }
}
