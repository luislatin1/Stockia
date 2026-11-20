<?php

namespace Tests\Feature\Dte;

use Carbon\Carbon;
use Stockia\DteSvMh\Builders\InvalidacionBuilder;
use Stockia\DteSvMh\Schemas\DteSchemaRegistry;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;
use Tests\TestCase;

class InvalidacionBuilderTest extends TestCase
{
    private function ctx(string $tipoDte, int $tipo, ?string $reemplazo = null, array $doc = []): array
    {
        return [
            'ambiente' => '00', 'codigo_generacion' => 'd1d2d3d4-e5f6-4789-9abc-def012345678',
            'fecha' => Carbon::parse('2026-11-20 15:10:00', 'America/El_Salvador'),
            'emisor' => ['nit' => '05122808871013', 'nombre' => 'EMPRESA SA', 'codEstableMH' => 'M001', 'codEstable' => 'M001',
                'codPuntoVentaMH' => 'P001', 'codPuntoVenta' => 'P001', 'telefono' => '22223333', 'correo' => 'v@x.com.sv'],
            'documento' => $doc + [
                'tipo_dte' => $tipoDte, 'codigo_generacion' => 'a1b2c3d4-e5f6-4789-9abc-def012345678',
                'sello' => str_repeat('A', 40), 'numero_control' => 'DTE-'.$tipoDte.'-M001P001-000000000000009',
                'fecha_emision' => '2026-11-10', 'reemplazo' => $reemplazo,
            ],
            'motivo' => ['tipo' => $tipo, 'texto' => 'Error en el monto',
                'responsable' => ['nombre' => 'ANA LOPEZ', 'tipo_doc' => '13', 'num_doc' => '012345678'],
                'solicita' => ['nombre' => 'ANA LOPEZ', 'tipo_doc' => '13', 'num_doc' => '012345678']],
        ];
    }

    private function validar(array $e): array
    {
        return (new DteSchemaValidator(new DteSchemaRegistry()))->validar($e, 'invalidacion');
    }

    public function test_invalidacion_de_ccf_con_reemplazo_cumple_el_esquema(): void
    {
        $e = (new InvalidacionBuilder())->build($this->ctx('03', 1, 'b1b2c3d4-e5f6-4789-9abc-def012345678'));

        $this->assertSame([], $this->validar($e));
        $this->assertSame('B1B2C3D4-E5F6-4789-9ABC-DEF012345678', $e['documento']['codigoGeneracionR']);
        $this->assertSame('2026-11-10', $e['identificacion']['fecEmi'], 'CCF: fecha del evento = fecha de generación del documento');
    }

    public function test_rescision_no_lleva_reemplazo_aunque_se_envie(): void
    {
        $e = (new InvalidacionBuilder())->build($this->ctx('03', 2, 'b1b2c3d4-e5f6-4789-9abc-def012345678'));
        $this->assertSame([], $this->validar($e));
        $this->assertNull($e['documento']['codigoGeneracionR']);
    }

    public function test_factura_usa_la_fecha_del_evento_de_hoy_y_receptor_opcional(): void
    {
        $e = (new InvalidacionBuilder())->build($this->ctx('01', 2, null, [
            'receptor' => ['tipoDocumento' => '13', 'numDocumento' => '012345678', 'nombre' => 'JUAN', 'telefono' => '22223333', 'correo' => 'j@x.com'],
        ]));
        $this->assertSame([], $this->validar($e));
        $this->assertSame('2026-11-20', $e['identificacion']['fecEmi']);
        $this->assertSame('JUAN', $e['documento']['nombre']);
    }

    public function test_los_eventos_no_tienen_numero_de_control(): void
    {
        $e = (new InvalidacionBuilder())->build($this->ctx('18', 2, null, ['numero_control' => 'E18-'.str_repeat('A', 36)]));
        $this->assertSame([], $this->validar($e));
        $this->assertNull($e['documento']['numeroControl']);
    }

    public function test_tipo_invalido_se_rechaza(): void
    {
        $this->expectException(\RuntimeException::class);
        (new InvalidacionBuilder())->build($this->ctx('03', 7));
    }
}
