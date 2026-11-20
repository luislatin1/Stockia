<?php

namespace Stockia\DteSvMh\Homologacion;

use Carbon\CarbonImmutable;
use Stockia\DteSvMh\Builders\ContingenciaBuilder;
use Stockia\DteSvMh\Builders\DteV2Builder;
use Stockia\DteSvMh\Builders\EventoRetornoBuilder;
use Stockia\DteSvMh\Builders\ExportacionBuilder;
use Stockia\DteSvMh\Builders\InvalidacionBuilder;
use Stockia\DteSvMh\Builders\SujetoExcluidoBuilder;

/**
 * Genera lotes de documentos de prueba SINTÉTICOS y variados (mezcla de ventas gravadas, exentas y no sujetas,
 * descuentos, decimales largos, contado y crédito, con y sin receptor, retenciones) usando los mismos
 * constructores que usa el sistema. No toca la base de datos. Determinista según la semilla.
 *
 * Mínimos de transmisión del Manual Funcional v2.0 §XXIV para la prueba de homologación.
 */
class GeneradorPruebas
{
    /** clave => [nombre, mínimo de pruebas, clave de esquema o null si no hay constructor] */
    public const CATALOGO = [
        '01' => ['Factura', 90, '01'],
        '03' => ['Comprobante de Crédito Fiscal', 75, '03'],
        '04' => ['Nota de Remisión', 50, null],
        '05' => ['Nota de Crédito', 50, '05'],
        '06' => ['Nota de Débito', 25, '06'],
        '07' => ['Comprobante de Retención', 50, null],
        '08' => ['Comprobante de Liquidación', 75, null],
        '09' => ['Documento Contable de Liquidación', 50, null],
        '11' => ['Factura de Exportación', 90, '11'],
        '14' => ['Factura de Sujeto Excluido', 25, '14'],
        '15' => ['Comprobante de Donación', 25, null],
        'invalidacion' => ['Evento de Invalidación', 5, 'invalidacion'],
        'contingencia' => ['Evento de Contingencia', 5, 'contingencia'],
        'retorno' => ['Evento de Retorno', 5, 'retorno'],
        'operaciones_especiales' => ['Evento de Operaciones Especiales', 5, null],
    ];

    private CarbonImmutable $ahora;

    private int $correlativo = 0;

    public function __construct(private readonly int $semilla = 2026, ?CarbonImmutable $ahora = null)
    {
        $this->ahora = $ahora ?? CarbonImmutable::now('America/El_Salvador');
    }

    /** @return \Generator<int, array> documentos listos para validar y firmar */
    public function generar(string $clave, int $cantidad): \Generator
    {
        mt_srand($this->semilla + crc32($clave));
        $this->correlativo = 0;

        for ($i = 0; $i < $cantidad; $i++) {
            yield match ($clave) {
                '01' => $this->factura($i),
                '03' => $this->ccf($i),
                '05' => $this->nota('05', $i),
                '06' => $this->nota('06', $i),
                '11' => $this->exportacion($i),
                '14' => $this->sujetoExcluido($i),
                'invalidacion' => $this->invalidacion($i),
                'contingencia' => $this->contingencia($i),
                'retorno' => $this->retorno($i),
                default => throw new \InvalidArgumentException("No hay constructor para '{$clave}'."),
            };
        }
    }

    // ------------------------------------------------------------------ piezas comunes

    private function r(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    private function pick(array $opciones): mixed
    {
        return $opciones[mt_rand(0, count($opciones) - 1)];
    }

    private function uuid(): string
    {
        $hex = '';
        for ($i = 0; $i < 32; $i++) {
            $hex .= dechex(mt_rand(0, 15));
        }
        $hex[12] = '4';
        $hex[16] = dechex(8 + mt_rand(0, 3));
        $hex = strtoupper($hex);

        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20, 12);
    }

    private function sello(): string
    {
        $s = '';
        for ($i = 0; $i < 40; $i++) {
            $s .= strtoupper(dechex(mt_rand(0, 15)));
        }

        return $s;
    }

    private function fecha(): CarbonImmutable
    {
        return $this->ahora->subSeconds($this->r(0, 3 * 3600));
    }

    private function control(string $tipo): string
    {
        $this->correlativo++;

        return sprintf('DTE-%s-M001P001-%015d', $tipo, $this->correlativo);
    }

    private function emisorBase(): array
    {
        return [
            'nit' => '05122808871013', 'nrc' => '231194', 'nombre' => 'EMPRESA DE PRUEBA SA DE CV', 'codActividad' => '47112',
            'descActividad' => 'Venta al por menor en comercios no especializados', 'nombreComercial' => 'EMPRESA DE PRUEBA',
            'direccion' => ['departamento' => '06', 'municipio' => '20', 'distrito' => '14', 'complemento' => 'Calle Principal 1, San Salvador'],
            'telefono' => '22223333', 'correo' => 'pruebas@ejemplo.com.sv', 'codEstable' => 'M001', 'codPuntoVenta' => 'P001',
        ];
    }

    private function direccion(): array
    {
        return $this->pick([
            ['departamento' => '01', 'municipio' => '14', 'distrito' => '01', 'complemento' => 'Col. Las Flores, casa 12'],
            ['departamento' => '06', 'municipio' => '20', 'distrito' => '14', 'complemento' => 'Av. Roosevelt 345, local 2'],
            ['departamento' => '12', 'municipio' => '23', 'distrito' => '01', 'complemento' => 'Barrio El Centro, 3a calle'],
        ]);
    }

    private function items(bool $mezcla = true, int $max = 5): array
    {
        $n = $this->r(1, $max);
        $items = [];
        for ($i = 0; $i < $n; $i++) {
            $cant = $this->pick([1, 1, 2, 3, 5, 10, 0.5, 1.25, 0.333, 12.5]);
            $precio = round($this->r(50, 25000) / 100 + $this->r(0, 99) / 10000, 4);
            $dsc = $this->r(0, 4) === 0 ? round($cant * $precio * $this->r(1, 10) / 100, 2) : 0.0;
            $cat = $mezcla ? $this->pick(['gravada', 'gravada', 'gravada', 'gravada', 'gravada', 'gravada', 'gravada', 'exenta', 'no_sujeta']) : 'gravada';
            $items[] = [
                'codigo' => $this->r(0, 3) === 0 ? null : 'P'.str_pad((string) $this->r(1, 999), 3, '0', STR_PAD_LEFT),
                'descripcion' => $this->pick(['Camisa de algodón', 'Servicio de instalación', 'Café molido 1 lb', 'Cuaderno universitario', 'Licencia de software', 'Flete local']),
                'cantidad' => $cant, 'precio' => $precio, 'descuento' => $dsc, 'categoria' => $cat,
                'tipo_item' => $this->pick([1, 1, 2]), 'uni_medida' => $this->pick([59, 59, 99]),
            ];
        }

        return $items;
    }

    private function sumaGravada(array $items): float
    {
        $t = 0.0;
        foreach ($items as $it) {
            if ($it['categoria'] === 'gravada') {
                $t += $it['cantidad'] * $it['precio'] - $it['descuento'];
            }
        }

        return $t;
    }

    private function condicion(): array
    {
        return $this->r(0, 4) === 0
            ? ['condicion_operacion' => 2, 'dias_credito' => $this->pick([8, 15, 30, 45, 60])]
            : ['condicion_operacion' => 1, 'forma_pago' => $this->pick(['01', '01', '02', '03', '05'])];
    }

    private function receptorFactura(): ?array
    {
        if ($this->r(0, 2) === 0) {
            return null; // consumidor final sin identificar
        }
        $dui = $this->r(0, 1) === 0;

        return [
            'tipoDocumento' => $dui ? '13' : '36',
            'numDocumento' => $dui ? str_pad((string) $this->r(1000000, 99999999), 9, '0', STR_PAD_LEFT) : '0614'.str_pad((string) $this->r(100000, 999999), 6, '0', STR_PAD_LEFT).'0010',
            'nrc' => null, 'nombre' => $this->pick(['JUAN PEREZ LOPEZ', 'MARIA GOMEZ', 'DISTRIBUIDORA EL SOL']),
            'codActividad' => null, 'descActividad' => null,
            'direccion' => $this->r(0, 1) === 0 ? $this->direccion() : null,
            'telefono' => $this->r(0, 1) === 0 ? '7'.str_pad((string) $this->r(0, 9999999), 7, '0', STR_PAD_LEFT) : null,
            'correo' => $this->r(0, 1) === 0 ? 'cliente'.$this->r(1, 999).'@correo.com' : null,
        ];
    }

    private function receptorCcf(): array
    {
        return [
            'nit' => '0614'.str_pad((string) $this->r(100000, 999999), 6, '0', STR_PAD_LEFT).'0010',
            'nrc' => (string) $this->r(10000, 999999), 'nombre' => $this->pick(['COMERCIAL LOS PINOS SA DE CV', 'SERVICIOS TECNICOS SA', 'IMPORTADORA DEL VALLE']),
            'codActividad' => '46900', 'descActividad' => 'Comercio al por mayor de otros productos', 'nombreComercial' => null,
            'direccion' => $this->direccion(), 'telefono' => '2200'.str_pad((string) $this->r(0, 9999), 4, '0', STR_PAD_LEFT), 'correo' => 'compras@empresa.com.sv',
        ];
    }

    private function base(string $tipo): array
    {
        return [
            'tipo_dte' => $tipo, 'ambiente' => '00', 'numero_control' => $this->control($tipo), 'codigo_generacion' => $this->uuid(),
            'fecha' => $this->fecha(), 'contingencia' => null, 'emisor' => $this->emisorBase(), 'tasa_iva' => 0.13,
        ];
    }

    // ------------------------------------------------------------------ documentos

    private function factura(int $i): array
    {
        $c = $this->base('01') + ['receptor' => $this->receptorFactura(), 'items' => $this->items(), 'iva_retenido' => 0] + $this->condicion();
        if ($i % 15 === 14) {
            $c['contingencia'] = ['tipo' => $this->r(1, 4), 'motivo' => 'Contingencia de prueba'];
        }

        return (new DteV2Builder())->build($c);
    }

    private function ccf(int $i): array
    {
        $items = $this->items();
        $ret = $this->r(0, 4) === 0 ? round($this->sumaGravada($items) * 0.01, 2) : 0.0;
        $c = $this->base('03') + ['receptor' => $this->receptorCcf(), 'items' => $items, 'iva_retenido' => $ret] + $this->condicion();

        return (new DteV2Builder())->build($c);
    }

    private function nota(string $tipo, int $i): array
    {
        $r = $this->receptorCcf();
        $items = $this->items();
        $c = $this->base($tipo) + [
            'receptor' => [
                'tipoDocumento' => '36', 'numDocumento' => $r['nit'], 'nrc' => $r['nrc'], 'nombre' => $r['nombre'], 'codActividad' => $r['codActividad'],
                'descActividad' => $r['descActividad'], 'nombreComercial' => null, 'direccion' => $r['direccion'], 'telefono' => $r['telefono'], 'correo' => $r['correo'],
            ],
            'documentos_relacionados' => [[
                'tipo_documento' => '03', 'tipo_generacion' => 2, 'numero' => $this->uuid(), 'fecha' => $this->ahora->subDays($this->r(0, 20))->format('Y-m-d'),
            ]],
            'items' => $items, 'tasa_retencion_iva' => $this->r(0, 4) === 0 ? 0.01 : 0.0,
            'observaciones' => $tipo === '05' ? 'Devolución de producto' : 'Cargo adicional',
        ] + $this->condicion();

        return (new DteV2Builder())->build($c);
    }

    private function exportacion(int $i): array
    {
        $bienes = $i % 4 !== 3;
        $emisor = $this->emisorBase();
        $emisor += $bienes
            ? ['tipoItemExpor' => 1, 'recintoFiscal' => '01', 'tipoRegimen' => 'EX-1', 'regimen' => '1000.000']
            : ['tipoItemExpor' => 2, 'recintoFiscal' => null, 'tipoRegimen' => null, 'regimen' => null];

        $items = array_map(fn ($it) => $it + ['tipo_item' => $bienes ? 1 : 2], $this->items(false));
        $inco = $bienes && $this->r(0, 1) === 0 ? $this->pick(['09', '11', '07']) : null;

        return (new ExportacionBuilder())->build([
            'ambiente' => '00', 'numero_control' => $this->control('11'), 'codigo_generacion' => $this->uuid(), 'fecha' => $this->fecha(),
            'contingencia' => null, 'emisor' => $emisor,
            'receptor' => [
                'nombre' => $this->pick(['ACME INC', 'GLOBAL TRADING LLC', 'COMERCIALIZADORA GUATEMALTECA SA']), 'tipoDocumento' => $this->pick(['03', '37']),
                'numDocumento' => 'X'.$this->r(1000000, 9999999), 'descActividad' => 'Comercio al por mayor', 'nombreComercial' => null,
                'codPais' => $this->pick(['US', 'GT', 'MX', 'DE']), 'nombrePais' => 'País de destino', 'complemento' => '100 Main Street',
                'tipoPersona' => $this->pick([1, 2]), 'telefono' => null, 'correo' => 'buyer@example.com',
            ],
            'items' => $items, 'seguro' => $this->r(0, 2) === 0 ? $this->r(5, 50) : 0, 'flete' => $this->r(0, 2) === 0 ? $this->r(10, 120) : 0,
            'cod_incoterms' => $inco, 'desc_incoterms' => $inco ? 'INCOTERM de prueba' : null,
        ] + $this->condicion());
    }

    private function sujetoExcluido(int $i): array
    {
        $emisor = $this->emisorBase();
        unset($emisor['nombreComercial']);
        $items = array_map(fn ($it) => $it, $this->items(false));
        $total = 0.0;
        foreach ($items as $it) {
            $total += $it['cantidad'] * $it['precio'] - $it['descuento'];
        }
        $renta = $this->r(0, 2) === 0 ? round($total * 0.10, 2) : 0.0;

        return (new SujetoExcluidoBuilder())->build([
            'ambiente' => '00', 'numero_control' => $this->control('14'), 'codigo_generacion' => $this->uuid(), 'fecha' => $this->fecha(),
            'contingencia' => null, 'emisor' => $emisor,
            'receptor' => [
                'tipoDocumento' => '13', 'numDocumento' => str_pad((string) $this->r(1000000, 99999999), 9, '0', STR_PAD_LEFT),
                'nombre' => $this->pick(['MARIA HERNANDEZ', 'JOSE MARTINEZ', 'ANA RODRIGUEZ']), 'codActividad' => null, 'descActividad' => null,
                'direccion' => $this->direccion(), 'telefono' => '7'.str_pad((string) $this->r(0, 9999999), 7, '0', STR_PAD_LEFT), 'correo' => null,
            ],
            'items' => $items, 'retencion_renta' => $renta,
        ] + $this->condicion());
    }

    private function invalidacion(int $i): array
    {
        $tipoDte = $this->pick(['01', '03', '05', '06', '11', '14', '18']);
        $tipo = $this->pick([1, 2, 3]);
        // Reemplazo: no aplica en rescisión, en NC ni en Evento de Retorno (Manual §VII).
        $reemplazo = $tipo !== 2 && ! in_array($tipoDte, ['05', '18'], true) ? $this->uuid() : null;
        $persona = ['nombre' => 'RESPONSABLE DE PRUEBA', 'tipo_doc' => '13', 'num_doc' => '012345678'];

        return (new InvalidacionBuilder())->build([
            'ambiente' => '00', 'codigo_generacion' => $this->uuid(), 'fecha' => $this->ahora,
            'emisor' => ['nit' => '05122808871013', 'nombre' => 'EMPRESA DE PRUEBA SA DE CV', 'codEstableMH' => 'M001', 'codEstable' => 'M001',
                'codPuntoVentaMH' => 'P001', 'codPuntoVenta' => 'P001', 'telefono' => '22223333', 'correo' => 'pruebas@ejemplo.com.sv'],
            'documento' => [
                'tipo_dte' => $tipoDte, 'codigo_generacion' => $this->uuid(), 'sello' => $this->sello(),
                'numero_control' => $tipoDte === '18' ? null : sprintf('DTE-%s-M001P001-%015d', $tipoDte, $i + 1),
                'fecha_emision' => $this->ahora->subDays($this->r(0, 25))->format('Y-m-d'), 'reemplazo' => $reemplazo,
                'receptor' => $this->r(0, 1) === 0 ? ['tipoDocumento' => '13', 'numDocumento' => '012345678', 'nombre' => 'JUAN PEREZ', 'telefono' => null, 'correo' => null] : null,
            ],
            'motivo' => ['tipo' => $tipo, 'texto' => 'Invalidación de prueba', 'responsable' => $persona, 'solicita' => $persona],
        ]);
    }

    private function contingencia(int $i): array
    {
        $tipo = $this->pick([1, 2, 3, 4, 5]);
        $docs = [];
        for ($k = 0; $k < $this->r(1, 12); $k++) {
            $docs[] = ['tipo_dte' => $this->pick(['01', '01', '03', '05', '06', '11', '14']), 'codigo_generacion' => $this->uuid()];
        }

        return (new ContingenciaBuilder())->build([
            'ambiente' => '00', 'codigo_generacion' => $this->uuid(), 'fecha' => $this->ahora,
            'inicio' => $this->ahora->subHours($this->r(2, 20)), 'fin' => $this->ahora->subMinutes($this->r(1, 60)),
            'tipo_contingencia' => $tipo, 'motivo' => $tipo === 5 ? 'Falla del proveedor de enlace' : ($this->r(0, 1) === 0 ? 'Corte de servicio' : null),
            'emisor' => [
                'nit' => '05122808871013', 'nombre' => 'EMPRESA DE PRUEBA SA DE CV',
                'responsable' => ['nombre' => 'RESPONSABLE DE PRUEBA', 'tipo_doc' => '13', 'num_doc' => '012345678'],
                'tipo_establecimiento' => $this->pick(['01', '02', '04', '07']), 'codEstableMH' => 'M001', 'codPuntoVentaMH' => 'P001',
                'telefono' => '22223333', 'correo' => 'pruebas@ejemplo.com.sv',
            ],
            'documentos' => $docs,
        ]);
    }

    private function retorno(int $i): array
    {
        $doc = $this->uuid();
        $items = array_map(fn ($it) => $it + ['documento' => $doc], $this->items(true, 4));

        return (new EventoRetornoBuilder())->build([
            'ambiente' => '00', 'codigo_generacion' => $this->uuid(), 'fecha' => $this->fecha(), 'contingencia' => null,
            'emisor' => ['nit' => '05122808871013', 'nombre' => 'EMPRESA DE PRUEBA SA DE CV', 'codEstableMH' => 'M001', 'codEstable' => 'M001',
                'codPuntoVentaMH' => 'P001', 'codPuntoVenta' => 'P001'],
            'documento' => $this->r(0, 1) === 0 ? ['tipoDocumento' => '13', 'numDocumento' => '012345678', 'nombre' => 'JUAN PEREZ', 'codPais' => null, 'nombrePais' => null, 'telefono' => null, 'correo' => null] : null,
            'relacionados' => [['tipo_documento' => '01', 'codigo_generacion' => $doc, 'fecha' => $this->ahora->subDays($this->r(0, 60))->format('Y-m-d')]],
            'items' => $items, 'tasa_iva' => 0.13,
        ]);
    }
}
