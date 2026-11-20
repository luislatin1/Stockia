<?php

namespace Tests\Feature\Dte;

use Carbon\Carbon;
use Stockia\DteSvMh\Builders\EventoRetornoBuilder;
use Stockia\DteSvMh\Schemas\DteSchemaRegistry;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;
use Tests\TestCase;

class EventoRetornoBuilderTest extends TestCase
{
    private function ctx(array $items, array $extra = []): array
    {
        return array_merge([
            'ambiente' => '00', 'codigo_generacion' => 'c1c2c3d4-e5f6-4789-9abc-def012345678',
            'fecha' => Carbon::parse('2026-11-20 14:05:09', 'America/El_Salvador'), 'contingencia' => null,
            'emisor' => ['nit' => '05122808871013', 'nombre' => 'EMPRESA SA', 'codEstableMH' => 'M001', 'codEstable' => 'M001', 'codPuntoVentaMH' => 'P001', 'codPuntoVenta' => 'P001'],
            'documento' => null,
            'relacionados' => [['tipo_documento' => '01', 'codigo_generacion' => 'a1b2c3d4-e5f6-4789-9abc-def012345678', 'fecha' => '2026-11-10']],
            'items' => $items, 'tasa_iva' => 0.13,
        ], $extra);
    }

    private function item(float $cant, float $precio, string $cat = 'gravada'): array
    {
        return ['documento' => 'A1B2C3D4-E5F6-4789-9ABC-DEF012345678', 'codigo' => 'P1', 'descripcion' => 'Silla', 'cantidad' => $cant,
            'precio' => $precio, 'categoria' => $cat, 'tipo_item' => 1, 'uni_medida' => 59];
    }

    private function validar(array $dte): array
    {
        return (new DteSchemaValidator(new DteSchemaRegistry()))->validar($dte, 'retorno');
    }

    public function test_retorno_de_factura_cumple_el_esquema_oficial(): void
    {
        $dte = (new EventoRetornoBuilder())->build($this->ctx([$this->item(2, 10.00), $this->item(1, 5.00, 'exenta')]));

        $this->assertSame([], $this->validar($dte));
        $this->assertSame('18', $dte['identificacion']['tipoEvento']);
        $this->assertArrayNotHasKey('numeroControl', $dte['identificacion']);
        $this->assertSame('A1B2C3D4-E5F6-4789-9ABC-DEF012345678', $dte['documentoRelacionado'][0]['codigoGeneracion']);
        // 2 × 10 × 1,13 = 22,60 gravado con IVA incluido; 5,00 exento.
        $this->assertEquals(22.6, $dte['resumen']['totalGravada']);
        $this->assertEquals(5.0, $dte['resumen']['totalExenta']);
        $this->assertEquals(27.6, $dte['resumen']['totalPagar']);
        $this->assertEquals(2.6, $dte['resumen']['totalIva']);
        $this->assertNull($dte['documento']);
    }

    public function test_con_receptor_y_en_contingencia(): void
    {
        $dte = (new EventoRetornoBuilder())->build($this->ctx([$this->item(1, 100.00)], [
            'documento' => ['tipoDocumento' => '13', 'numDocumento' => '012345678', 'nombre' => 'JUAN PEREZ', 'codPais' => null, 'nombrePais' => null, 'telefono' => '22223333', 'correo' => 'j@x.com'],
            'contingencia' => ['tipo' => 3, 'motivo' => 'Sin internet'],
        ]));

        $this->assertSame([], $this->validar($dte));
        $this->assertSame(2, $dte['identificacion']['tipoOperacion']);
        $this->assertSame('JUAN PEREZ', $dte['documento']['nombre']);
        $this->assertNull($dte['identificacion']['motivoContin']);

        $otro = (new EventoRetornoBuilder())->build($this->ctx([$this->item(1, 100.00)], ['contingencia' => ['tipo' => 5, 'motivo' => 'Falla del proveedor']]));
        $this->assertSame([], $this->validar($otro));
        $this->assertSame('Falla del proveedor', $otro['identificacion']['motivoContin']);
    }

    public function test_solo_facturas_y_al_menos_un_item(): void
    {
        $b = new EventoRetornoBuilder();
        $this->expectException(\RuntimeException::class);
        $b->build($this->ctx([$this->item(1, 1.0)], ['relacionados' => [['tipo_documento' => '03', 'codigo_generacion' => 'A1B2C3D4-E5F6-4789-9ABC-DEF012345678', 'fecha' => '2026-11-10']]]));
    }
}
