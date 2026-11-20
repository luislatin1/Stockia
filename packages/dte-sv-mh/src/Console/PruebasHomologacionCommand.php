<?php

namespace Stockia\DteSvMh\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Stockia\DteSvMh\Homologacion\GeneradorPruebas;
use Stockia\DteSvMh\Homologacion\VerificadorAritmetico;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;

/**
 * Checklist de la prueba de homologación (Manual Funcional v2.0 §XXIV): genera el mínimo de documentos de
 * prueba por tipo con los mismos constructores del sistema, los valida contra el esquema oficial y recalcula su
 * aritmética. Opcionalmente los guarda como JSON para firmarlos y transmitirlos en el ambiente de pruebas.
 *
 * Esto prueba la ESTRUCTURA y el CÁLCULO. No sustituye la transmisión real: el sello de Hacienda solo se obtiene
 * firmando y enviando al ambiente de pruebas (pendiente de firmador, credenciales y Manual Tecnológico).
 */
class PruebasHomologacionCommand extends Command
{
    protected $signature = 'dte:pruebas-homologacion
        {--tipo=* : tipos a probar (01, 03, 05, 06, 11, 14, invalidacion, retorno); por defecto todos los soportados}
        {--cantidad= : cantidad por tipo en lugar del mínimo oficial}
        {--semilla=2026 : semilla de los datos de prueba (mismos datos con la misma semilla)}
        {--exportar= : carpeta donde guardar los JSON generados (uno por documento) y resumen.json}';

    protected $description = 'Genera y verifica el mínimo de documentos de prueba que exige la homologación de Hacienda';

    public function handle(DteSchemaValidator $validador): int
    {
        $catalogo = GeneradorPruebas::CATALOGO;
        $pedidos = array_map('strval', (array) $this->option('tipo'));
        foreach ($pedidos as $p) {
            if (! isset($catalogo[$p]) || $catalogo[$p][2] === null) {
                $this->error("No hay constructor para '{$p}'. Soportados: ".implode(', ', $this->soportados()).'.');

                return self::INVALID;
            }
        }

        $semilla = (int) $this->option('semilla');
        $cantidadFija = $this->option('cantidad') !== null ? max(1, (int) $this->option('cantidad')) : null;
        $generador = new GeneradorPruebas($semilla);
        $carpeta = $this->option('exportar') ? rtrim((string) $this->option('exportar'), '/\\') : null;
        if ($carpeta) {
            File::ensureDirectoryExists($carpeta);
        }

        $filas = [];
        $detalle = [];
        $fallas = 0;
        $inicio = microtime(true);

        foreach ($catalogo as $clave => [$nombre, $minimo, $esquema]) {
            if ($pedidos && ! in_array($clave, $pedidos, true)) {
                continue;
            }
            if ($esquema === null) {
                $filas[] = [$clave, $nombre, $minimo, 0, 0, 0, 'SIN CONSTRUCTOR'];

                continue;
            }

            $requeridas = $cantidadFija ?? $minimo;
            $okEsquema = 0;
            $okAritmetica = 0;
            $errores = [];
            $n = 0;

            foreach ($generador->generar($clave, $requeridas) as $doc) {
                $n++;
                $eEsq = $validador->validar($doc, $esquema);
                $eAri = VerificadorAritmetico::verificar($doc, $esquema);
                $eEsq === [] && $okEsquema++;
                $eAri === [] && $okAritmetica++;

                if ($eEsq || $eAri) {
                    $errores[] = ['n' => $n, 'esquema' => array_slice($eEsq, 0, 3), 'aritmetica' => array_slice($eAri, 0, 3)];
                }
                if ($carpeta) {
                    File::ensureDirectoryExists("{$carpeta}/{$clave}");
                    File::put(sprintf('%s/%s/%03d.json', $carpeta, $clave, $n), json_encode($doc, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                }
            }

            $bien = $okEsquema === $requeridas && $okAritmetica === $requeridas;
            $fallas += $bien ? 0 : 1;
            $filas[] = [$clave, $nombre, $requeridas, $n, $okEsquema, $okAritmetica, $bien ? 'OK' : 'FALLA'];
            $detalle[$clave] = ['requeridas' => $requeridas, 'generadas' => $n, 'ok_esquema' => $okEsquema, 'ok_aritmetica' => $okAritmetica, 'errores' => $errores];
        }

        $this->table(['Clave', 'Documento', 'Requeridas', 'Generadas', 'Válidas esquema', 'Válidas cálculo', 'Estado'], $filas);

        foreach ($detalle as $clave => $d) {
            foreach (array_slice($d['errores'], 0, 3) as $e) {
                $this->line("  {$clave} #{$e['n']}: ".json_encode(['esquema' => $e['esquema'], 'cálculo' => $e['aritmetica']], JSON_UNESCAPED_UNICODE));
            }
        }

        $sin = array_filter($filas, fn ($f) => $f[6] === 'SIN CONSTRUCTOR');
        if ($sin) {
            $this->newLine();
            $this->warn('Sin constructor (no se pueden generar): '.implode(', ', array_map(fn ($f) => "{$f[1]} ({$f[0]})", $sin)).'.');
        }

        $this->newLine();
        $this->line(sprintf('Semilla %d · %.1f s · estructura y cálculo verificados; falta firmar y transmitir al ambiente de pruebas de Hacienda.', $semilla, microtime(true) - $inicio));

        if ($carpeta) {
            File::put("{$carpeta}/resumen.json", json_encode([
                'generado' => now()->toIso8601String(), 'semilla' => $semilla, 'detalle' => $detalle,
                'nota' => 'Documentos sintéticos sin firmar. Prueban estructura y cálculo, no la transmisión.',
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->info("JSON guardados en {$carpeta}");
        }

        return $fallas === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return string[] */
    private function soportados(): array
    {
        return array_keys(array_filter(GeneradorPruebas::CATALOGO, fn ($c) => $c[2] !== null));
    }
}
