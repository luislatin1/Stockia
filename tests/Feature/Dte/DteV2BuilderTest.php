<?php

namespace Tests\Feature\Dte;

use Carbon\Carbon;
use Stockia\DteSvMh\Builders\DteV2Builder;
use Stockia\DteSvMh\Schemas\DteSchemaRegistry;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;
use Tests\TestCase;

/** El constructor v2 debe producir documentos que cumplan el esquema oficial de Hacienda. */
class DteV2BuilderTest extends TestCase
{
    private function ctx(string $tipo, array $items, array $extra = []): array
    {
        $receptorCcf = [
            'nit' => '06141234567890', 'nrc' => '123456', 'nombre' => 'CLIENTE SA DE CV',
            'codActividad' => '47112', 'descActividad' => 'Venta al por menor', 'nombreComercial' => null,
            'direccion' => ['departamento' => '06', 'municipio' => '14', 'distrito' => '01', 'complemento' => 'Col. Escalón #5'],
            'telefono' => '22001122', 'correo' => 'c@x.com.sv',
        ];

        return array_merge([
            'tipo_dte' => $tipo, 'ambiente' => '00',
            'numero_control' => "DTE-{$tipo}-M001P001-000000000000007",
            'codigo_generacion' => 'a1b2c3d4-e5f6-4789-9abc-def012345678',
            'fecha' => Carbon::parse('2026-11-20 14:05:09', 'America/El_Salvador'),
            'contingencia' => null,
            'emisor' => [
                'nit' => '05122808871013', 'nrc' => '231194', 'nombre' => 'EMPRESA SA', 'codActividad' => '47112',
                'descActividad' => 'Venta al por menor', 'nombreComercial' => null,
                'direccion' => ['departamento' => '06', 'municipio' => '21', 'distrito' => '01', 'complemento' => 'Calle 1'],
                'telefono' => '22223333', 'correo' => 'v@x.com.sv', 'codEstable' => 'M001', 'codPuntoVenta' => 'P001',
            ],
            'receptor' => $tipo === '03' ? $receptorCcf : null,
            'items' => $items, 'tasa_iva' => 0.13, 'iva_retenido' => 0,
            'condicion_operacion' => 1, 'forma_pago' => '01', 'dias_credito' => 30,
        ], $extra);
    }

    private function item(float $cant, float $precio, float $descu = 0, string $cat = 'gravada'): array
    {
        return ['codigo' => 'P1', 'descripcion' => 'Producto', 'cantidad' => $cant, 'precio' => $precio,
            'descuento' => $descu, 'categoria' => $cat, 'tipo_item' => 1, 'uni_medida' => 59];
    }

    private function validar(array $dte, string $tipo): array
    {
        return (new DteSchemaValidator(new DteSchemaRegistry()))->validar($dte, $tipo);
    }

    public function test_factura_cumple_el_esquema_y_el_iva_va_incluido(): void
    {
        $dte = (new DteV2Builder())->build($this->ctx('01', [$this->item(3, 10.00), $this->item(1, 5.00, 0.50)]));

        $this->assertSame([], $this->validar($dte, '01'));
        $this->assertSame(2, $dte['identificacion']['version']);
        $this->assertSame(11.30, $dte['cuerpoDocumento'][0]['precioUni']);
        // (30 + 4.5) * 1.13 = 38.985 → 38.99 ; IVA incluido = 38.985/1.13*0.13 = 4.485 → 4.49
        $this->assertSame(38.99, $dte['resumen']['totalGravada']);
        $this->assertSame(38.99, $dte['resumen']['totalPagar']);
        $this->assertEqualsWithDelta(4.49, $dte['resumen']['totalIva'], 0.011);
        $this->assertNull($dte['resumen']['tributos']);
        $this->assertNull($dte['receptor']);
    }

    public function test_ccf_cumple_el_esquema_y_el_iva_se_suma(): void
    {
        $dte = (new DteV2Builder())->build($this->ctx('03', [$this->item(2, 50.00)]));

        $this->assertSame([], $this->validar($dte, '03'));
        $this->assertSame(4, $dte['identificacion']['version']);
        $this->assertSame(['20'], $dte['cuerpoDocumento'][0]['tributos']);
        $this->assertArrayNotHasKey('ivaItem', $dte['cuerpoDocumento'][0]);
        $this->assertSame(100.0, $dte['resumen']['totalGravada']);
        $this->assertSame(13.0, $dte['resumen']['tributos'][0]['valor']);
        $this->assertSame(113.0, $dte['resumen']['montoTotalOperacion']);
        $this->assertSame(113.0, $dte['resumen']['totalPagar']);
    }

    public function test_ccf_con_retencion_de_iva_resta_del_total(): void
    {
        $dte = (new DteV2Builder())->build($this->ctx('03', [$this->item(1, 200.00)], ['iva_retenido' => 2.00]));

        $this->assertSame([], $this->validar($dte, '03'));
        $this->assertSame(226.0, $dte['resumen']['montoTotalOperacion']);
        $this->assertSame(224.0, $dte['resumen']['totalPagar']);
        $this->assertSame(2.0, $dte['resumen']['ivaRete']);
    }

    public function test_mezcla_de_gravado_exento_y_no_sujeto(): void
    {
        $dte = (new DteV2Builder())->build($this->ctx('01', [
            $this->item(1, 100.00), $this->item(2, 7.50, 0, 'exenta'), $this->item(1, 3.00, 0, 'no_sujeta'),
        ]));

        $this->assertSame([], $this->validar($dte, '01'));
        $this->assertSame(113.0, $dte['resumen']['totalGravada']);
        $this->assertSame(15.0, $dte['resumen']['totalExenta']);
        $this->assertSame(3.0, $dte['resumen']['totalNoSuj']);
        $this->assertSame(131.0, $dte['resumen']['totalPagar']);
        $this->assertSame(0.0, $dte['cuerpoDocumento'][1]['ivaItem']);
    }

    public function test_credito_lleva_plazo_y_sin_forma_de_pago(): void
    {
        $dte = (new DteV2Builder())->build($this->ctx('03', [$this->item(1, 10.00)], ['condicion_operacion' => 2, 'dias_credito' => 30]));

        $this->assertSame([], $this->validar($dte, '03'));
        $this->assertSame(2, $dte['resumen']['condicionOperacion']);
        $this->assertNull($dte['resumen']['pagos'][0]['codigo']);
        $this->assertSame(0.0, $dte['resumen']['pagos'][0]['montoPago']);
        $this->assertSame(30, (int) $dte['resumen']['pagos'][0]['periodo']);
    }

    public function test_contingencia_usa_modelo_y_operacion_2(): void
    {
        $dte = (new DteV2Builder())->build($this->ctx('01', [$this->item(1, 1.00)], ['contingencia' => ['tipo' => 1, 'motivo' => 'Sin internet']]));

        $this->assertSame([], $this->validar($dte, '01'));
        $this->assertSame(2, $dte['identificacion']['tipoOperacion']);
        $this->assertSame(1, $dte['identificacion']['tipoContingencia']);
    }

    public function test_hora_y_fecha_en_hora_de_el_salvador(): void
    {
        $dte = (new DteV2Builder())->build($this->ctx('01', [$this->item(1, 1.00)]));
        $this->assertSame('2026-11-20', $dte['identificacion']['fecEmi']);
        $this->assertSame('14:05:09', $dte['identificacion']['horEmi']);
        $this->assertSame('A1B2C3D4-E5F6-4789-9ABC-DEF012345678', $dte['identificacion']['codigoGeneracion']);
    }

    public function test_monto_en_letras(): void
    {
        $b = new DteV2Builder();
        $this->assertSame('CERO 00/100 DÓLARES', $b->aLetras(0));
        $this->assertSame('UN 01/100 DÓLARES', $b->aLetras(1.01));
        $this->assertSame('CIEN 00/100 DÓLARES', $b->aLetras(100));
        $this->assertSame('CIENTO TRECE 50/100 DÓLARES', $b->aLetras(113.5));
        $this->assertSame('MIL 00/100 DÓLARES', $b->aLetras(1000));
        $this->assertSame('DOS MIL QUINIENTOS TREINTA Y UN 99/100 DÓLARES', $b->aLetras(2531.99));
        $this->assertSame('UN MILLÓN DOSCIENTOS MIL 00/100 DÓLARES', $b->aLetras(1200000));
    }

    public function test_tipos_no_soportados_se_rechazan(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DteV2Builder())->build($this->ctx('07', [$this->item(1, 1.00)]));
    }

    public function test_el_validador_no_marca_como_invalido_un_monto_de_dos_decimales_por_error_de_coma_flotante(): void
    {
        // 4.35 y 3.39 fallan con fmod() en coma flotante aunque sean múltiplos exactos de 0.01.
        foreach ([[1, 4.35 / 1.13], [3, 1.13 / 1.13]] as [$cant, $precio]) {
            $dte = (new DteV2Builder())->build($this->ctx('01', [$this->item($cant, $precio)]));
            $this->assertSame([], $this->validar($dte, '01'));
        }
        $dte = (new DteV2Builder())->build($this->ctx('01', [$this->item(1, 4.35 / 1.13)]));
        $this->assertSame(4.35, $dte['resumen']['totalPagar']);
    }

    public function test_un_monto_con_tres_decimales_si_se_rechaza(): void
    {
        $dte = (new DteV2Builder())->build($this->ctx('01', [$this->item(1, 1.00)]));
        $dte['resumen']['totalPagar'] = 1.125;
        $this->assertNotSame([], $this->validar($dte, '01'));
    }

    /** Contexto de una nota (05/06) sobre un CCF: receptor con tipoDocumento/numDocumento y documento relacionado. */
    private function ctxNota(string $tipo, array $items, array $extra = []): array
    {
        $c = $this->ctx('03', $items);
        $r = $c['receptor'];
        $c['tipo_dte'] = $tipo;
        $c['numero_control'] = "DTE-{$tipo}-M001P001-000000000000003";
        $c['receptor'] = [
            'tipoDocumento' => '36', 'numDocumento' => $r['nit'], 'nrc' => $r['nrc'], 'nombre' => $r['nombre'],
            'codActividad' => $r['codActividad'], 'descActividad' => $r['descActividad'], 'nombreComercial' => null,
            'direccion' => $r['direccion'], 'telefono' => $r['telefono'], 'correo' => $r['correo'],
        ];
        $c['documentos_relacionados'] = [[
            'tipo_documento' => '03', 'tipo_generacion' => 2,
            'numero' => 'b1b2c3d4-e5f6-4789-9abc-def012345678', 'fecha' => '2026-11-10',
        ]];

        return array_merge($c, $extra);
    }

    public function test_nota_de_credito_cumple_el_esquema_v4(): void
    {
        $dte = (new DteV2Builder())->build($this->ctxNota('05', [$this->item(2, 50.00), $this->item(1, 7.50, 0, 'exenta')]));

        $this->assertSame([], $this->validar($dte, '05'));
        $this->assertSame(4, $dte['identificacion']['version']);
        $this->assertArrayNotHasKey('codEstable', $dte['emisor']);
        $this->assertSame('B1B2C3D4-E5F6-4789-9ABC-DEF012345678', $dte['documentoRelacionado'][0]['numeroDocumento']);
        $this->assertSame('B1B2C3D4-E5F6-4789-9ABC-DEF012345678', $dte['cuerpoDocumento'][0]['numeroDocumento']);
        $this->assertSame(13.0, $dte['cuerpoDocumento'][0]['totalIva']);
        $this->assertSame(0.0, $dte['cuerpoDocumento'][1]['totalIva']);
        $this->assertSame(100.0, $dte['resumen']['totalGravada']);
        $this->assertSame(13.0, $dte['resumen']['totalIva']);
        $this->assertSame(120.5, $dte['resumen']['totalPagar']);
        $this->assertArrayNotHasKey('pagos', $dte['resumen']);
        $this->assertNull($dte['resumen']['codigoRetencionMH']);
    }

    public function test_nota_de_debito_cumple_el_esquema_v4(): void
    {
        $dte = (new DteV2Builder())->build($this->ctxNota('06', [$this->item(1, 25.00)]));

        $this->assertSame([], $this->validar($dte, '06'));
        $this->assertSame('06', $dte['identificacion']['tipoDte']);
        $this->assertSame(28.25, $dte['resumen']['totalPagar']);
    }

    public function test_nota_con_retencion_de_iva_resta_y_marca_el_codigo(): void
    {
        $dte = (new DteV2Builder())->build($this->ctxNota('05', [$this->item(1, 200.00)], ['tasa_retencion_iva' => 0.01]));

        $this->assertSame([], $this->validar($dte, '05'));
        $this->assertSame(2.0, $dte['cuerpoDocumento'][0]['ivaRete']);
        $this->assertSame(2.0, $dte['resumen']['ivaRete']);
        $this->assertSame('22', $dte['resumen']['codigoRetencionMH']);
        $this->assertSame(224.0, $dte['resumen']['totalPagar']);
    }

    public function test_nota_exige_documento_relacionado_ccf_con_uuid(): void
    {
        $b = new DteV2Builder();
        foreach ([[], [['tipo_documento' => '01', 'numero' => 'B1B2C3D4-E5F6-4789-9ABC-DEF012345678', 'fecha' => '2026-11-10']],
            [['tipo_documento' => '03', 'numero' => 'no-es-uuid', 'fecha' => '2026-11-10']]] as $docs) {
            try {
                $b->build($this->ctxNota('05', [$this->item(1, 1.00)], ['documentos_relacionados' => $docs]));
                $this->fail('Debió rechazar el documento relacionado');
            } catch (\RuntimeException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }
}
