<?php

namespace Stockia\DteSvMh\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stockia\DteSvMh\Builders\DteV2Builder;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;

/**
 * Repite las ventas históricas (legacy_sales) por el constructor v2 como si fueran Facturas (01) y
 * mide cuántas cumplen el esquema oficial y cuadran con el total original. Solo LEE: no escribe nada.
 *
 * Emisor y receptor son sintéticos y válidos; lo que se prueba son los ítems y los cálculos.
 * Los precios históricos se toman como precio final de consumidor (IVA incluido), que es lo que
 * exige la Factura; el IVA original era 0.00 en el sistema viejo.
 */
class ProbarHistoricoCommand extends Command
{
    protected $signature = 'dte:probar-historico
        {--company= : id de empresa (por defecto todas)}
        {--limit=0 : máximo de ventas a probar (0 = todas)}
        {--desde= : fecha mínima AAAA-MM-DD}
        {--sin-esquema : solo cálculo, sin validar contra el esquema (más rápido)}';

    protected $description = 'Prueba el constructor DTE v2 contra las ventas históricas (solo lectura)';

    public function handle(DteV2Builder $builder, DteSchemaValidator $validador): int
    {
        $limite = (int) $this->option('limit');
        $conEsquema = ! $this->option('sin-esquema');

        $q = DB::table('legacy_sales')->where('anulada', false)->where('tipo', 'TICKET')->orderBy('id');
        if ($this->option('company')) {
            $q->where('company_id', (int) $this->option('company'));
        }
        if ($this->option('desde')) {
            $q->where('fecha', '>=', $this->option('desde'));
        }
        if ($limite > 0) {
            $q->limit($limite);
        }

        $ventas = 0;
        $lineas = 0;
        $excluidas = [];
        $erroresEsquema = [];
        $ventasConError = 0;
        $descuadres = 0;
        $maxDif = 0.0;
        $ejemplos = [];
        $inicio = microtime(true);

        $bar = $this->output->createProgressBar($limite > 0 ? $limite : (clone $q)->count());

        $q->chunkById(1000, function ($chunk) use (&$ventas, &$lineas, &$excluidas, &$erroresEsquema, &$ventasConError, &$descuadres, &$maxDif, &$ejemplos, $builder, $validador, $conEsquema, $bar) {
            $items = DB::table('legacy_sale_items')
                ->whereIn('legacy_sale_id', $chunk->pluck('id'))
                ->orderBy('id')->get()->groupBy('legacy_sale_id');

            foreach ($chunk as $v) {
                $bar->advance();
                $its = $items->get($v->id, collect());
                $cuerpo = [];
                foreach ($its as $i) {
                    $cant = (float) $i->cantidad;
                    $sub = (float) $i->subtotal;
                    if ($cant <= 0 || $sub <= 0) {
                        $excluidas['línea con cantidad o subtotal <= 0'] = ($excluidas['línea con cantidad o subtotal <= 0'] ?? 0) + 1;
                        continue;
                    }
                    $exento = (bool) $i->exento;
                    $bruto = $sub / $cant;
                    $cuerpo[] = [
                        'codigo' => (string) $i->legacy_id,
                        'descripcion' => $i->descripcion ?: 'ITEM',
                        // El builder recibe precio SIN IVA y le suma el 13 % en las líneas gravadas.
                        'precio' => $exento ? $bruto : $bruto / 1.13,
                        'descuento' => 0,
                        'cantidad' => $cant,
                        'categoria' => $exento ? 'exenta' : 'gravada',
                        'tipo_item' => 1,
                        'uni_medida' => 59,
                    ];
                }

                if ($cuerpo === []) {
                    $excluidas['venta sin líneas utilizables'] = ($excluidas['venta sin líneas utilizables'] ?? 0) + 1;
                    continue;
                }
                if (count($cuerpo) > 2000) {
                    $excluidas['más de 2000 líneas'] = ($excluidas['más de 2000 líneas'] ?? 0) + 1;
                    continue;
                }

                $ventas++;
                $lineas += count($cuerpo);

                $dte = $builder->build($this->contexto($v, $cuerpo));

                // Esperado: la suma de subtotales de línea tomados como finales.
                $esperado = round(array_sum(array_map(fn ($c) => $c['cantidad'] * $c['precio'] * ($c['categoria'] === 'gravada' ? 1.13 : 1), $cuerpo)), 2);
                $dif = abs($dte['resumen']['totalPagar'] - $esperado);
                $maxDif = max($maxDif, $dif);
                if ($dif > 0.01) {
                    $descuadres++;
                    if (count($ejemplos) < 5) {
                        $ejemplos[] = "venta legacy #{$v->id}: DTE {$dte['resumen']['totalPagar']} vs esperado {$esperado}";
                    }
                }

                if ($conEsquema) {
                    $errores = $validador->validar($dte, '01');
                    if ($errores) {
                        $ventasConError++;
                        foreach ($errores as $e) {
                            $k = $e['ruta'].' → '.$e['mensaje'];
                            $erroresEsquema[$k] = ($erroresEsquema[$k] ?? 0) + 1;
                        }
                        if (count($ejemplos) < 5) {
                            $ejemplos[] = "venta legacy #{$v->id}: ".$errores[0]['ruta'].' '.$errores[0]['mensaje'];
                        }
                    }
                }
            }
        });
        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf('Ventas probadas: %s  ·  líneas: %s  ·  %.1f s', number_format($ventas), number_format($lineas), microtime(true) - $inicio));
        foreach ($excluidas as $motivo => $n) {
            $this->line("  excluido ({$motivo}): ".number_format($n));
        }
        $this->line($conEsquema
            ? '  Ventas que NO cumplen el esquema: '.number_format($ventasConError)
            : '  Esquema: omitido (--sin-esquema)');
        foreach (array_slice($erroresEsquema, 0, 10, true) as $k => $n) {
            $this->line("    {$n} × {$k}");
        }
        $this->line('  Ventas con total distinto al esperado (> 0,01): '.number_format($descuadres).sprintf('  (mayor diferencia: %.4f)', $maxDif));
        foreach ($ejemplos as $e) {
            $this->line('    '.$e);
        }

        return ($ventasConError + $descuadres) === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function contexto(object $v, array $cuerpo): array
    {
        return [
            'tipo_dte' => '01', 'ambiente' => '00',
            'numero_control' => 'DTE-01-M001P001-'.str_pad((string) $v->id, 15, '0', STR_PAD_LEFT),
            'codigo_generacion' => 'A1B2C3D4-E5F6-4789-9ABC-DEF012345678',
            'fecha' => Carbon::parse($v->fecha.' '.($v->hora ?: '00:00:00'), 'America/El_Salvador'),
            'contingencia' => null,
            'emisor' => [
                'nit' => '05122808871013', 'nrc' => '231194', 'nombre' => 'EMPRESA DE PRUEBA', 'codActividad' => '47112',
                'descActividad' => 'Venta al por menor', 'nombreComercial' => null,
                'direccion' => ['departamento' => '06', 'municipio' => '21', 'distrito' => '01', 'complemento' => 'Calle 1'],
                'telefono' => '22223333', 'correo' => 'prueba@ejemplo.com.sv', 'codEstable' => 'M001', 'codPuntoVenta' => 'P001',
            ],
            'receptor' => null,
            'items' => $cuerpo,
            'tasa_iva' => 0.13, 'iva_retenido' => 0,
            'condicion_operacion' => 1, 'forma_pago' => '01', 'dias_credito' => 1,
        ];
    }
}
