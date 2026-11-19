<?php

namespace Stockia\DteSvMh\Schemas;

use Carbon\CarbonInterface;

/**
 * Catálogo de los esquemas JSON oficiales de Hacienda incluidos en
 * resources/schemas, con sus versiones.
 *
 * Es la pieza que permite absorber cambios de Hacienda sin reescribir el módulo:
 * un esquema nuevo es un archivo más y una versión más en manifest.json. Durante
 * una transición pueden convivir dos versiones de un mismo documento; la que se
 * usa se decide así, en orden:
 *   1. la versión pedida explícitamente;
 *   2. la fijada en config('dte.esquemas.<clave>') (interruptor de emergencia);
 *   3. la más alta cuyo `vigente_desde` ya llegó (null = siempre vigente).
 *
 * Claves: el código de tipo de documento ('01', '03', ...) o el nombre del evento
 * ('invalidacion', 'contingencia', 'retorno', 'operaciones_especiales').
 */
class DteSchemaRegistry
{
    private ?array $manifest = null;

    public function __construct(private readonly ?string $directorio = null)
    {
    }

    public function directorio(): string
    {
        return $this->directorio ?? dirname(__DIR__, 2).'/resources/schemas';
    }

    /** @return array<string, array{tipo:string, nombre:string, versiones:array}> */
    public function esquemas(): array
    {
        return $this->manifest()['esquemas'];
    }

    public function existe(string $clave): bool
    {
        return isset($this->esquemas()[$clave]);
    }

    public function nombre(string $clave): string
    {
        return $this->entrada($clave)['nombre'];
    }

    /** @return int[] versiones disponibles, de menor a mayor */
    public function versiones(string $clave): array
    {
        $versiones = array_map('intval', array_keys($this->entrada($clave)['versiones']));
        sort($versiones);

        return $versiones;
    }

    public function version(string $clave, ?int $forzada = null, ?CarbonInterface $fecha = null): int
    {
        $disponibles = $this->versiones($clave);

        if ($forzada !== null) {
            return $this->exigirVersion($clave, $forzada, $disponibles);
        }

        $configurada = function_exists('config') ? config("dte.esquemas.$clave") : null;
        if ($configurada !== null && $configurada !== '') {
            return $this->exigirVersion($clave, (int) $configurada, $disponibles);
        }

        $dia = ($fecha ?? now())->copy()
            ->setTimezone(function_exists('config') ? config('dte.timezone', 'America/El_Salvador') : 'America/El_Salvador')
            ->toDateString();

        $vigentes = array_filter($disponibles, function (int $v) use ($clave, $dia) {
            $desde = $this->entrada($clave)['versiones'][(string) $v]['vigente_desde'] ?? null;

            return $desde === null || $desde <= $dia;
        });

        if (! $vigentes) {
            throw new \RuntimeException("Ninguna versión del esquema '$clave' está vigente en $dia.");
        }

        return max($vigentes);
    }

    public function ruta(string $clave, int $version): string
    {
        $archivo = $this->entrada($clave)['versiones'][(string) $version]['archivo'] ?? null;
        if ($archivo === null) {
            throw new \InvalidArgumentException("El esquema '$clave' no tiene la versión $version.");
        }

        return $this->directorio().'/'.$archivo;
    }

    /**
     * Compara cada archivo con la huella registrada.
     *
     * @return array<string, string> archivo => motivo; vacío si todo está íntegro
     */
    public function problemasDeIntegridad(): array
    {
        $problemas = [];

        foreach ($this->esquemas() as $datos) {
            foreach ($datos['versiones'] as $info) {
                $ruta = $this->directorio().'/'.$info['archivo'];

                if (! is_file($ruta)) {
                    $problemas[$info['archivo']] = 'falta el archivo';
                    continue;
                }

                // Mismo criterio que al generar el manifiesto: contenido con LF.
                $contenido = str_replace("\r\n", "\n", (string) file_get_contents($ruta));
                if (hash('sha256', $contenido) !== $info['sha256']) {
                    $problemas[$info['archivo']] = 'la huella sha256 no coincide (archivo modificado)';
                }
            }
        }

        return $problemas;
    }

    private function entrada(string $clave): array
    {
        return $this->esquemas()[$clave]
            ?? throw new \InvalidArgumentException("No hay esquema para '$clave'. Claves: ".implode(', ', array_keys($this->esquemas())));
    }

    private function exigirVersion(string $clave, int $version, array $disponibles): int
    {
        if (! in_array($version, $disponibles, true)) {
            throw new \InvalidArgumentException(
                "El esquema '$clave' no tiene la versión $version (disponibles: ".implode(', ', $disponibles).').'
            );
        }

        return $version;
    }

    private function manifest(): array
    {
        return $this->manifest ??= json_decode(
            (string) file_get_contents($this->directorio().'/manifest.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
