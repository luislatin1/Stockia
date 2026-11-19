<?php

namespace Stockia\DteSvMh\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stockia\DteSvMh\Schemas\DteSchemaRegistry;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;

class EsquemasCommand extends Command
{
    protected $signature = 'dte:esquemas
        {--validar= : ruta de un archivo JSON a validar contra el esquema}
        {--dte= : id de un DTE guardado (dtes.json_original) a validar}
        {--clave= : tipo de documento (01, 03...) o evento (invalidacion...); por defecto se toma del documento}
        {--esquema-version= : versión del esquema; por defecto la vigente}';

    protected $description = 'Lista los esquemas oficiales de Hacienda, verifica su integridad y valida documentos contra ellos';

    public function handle(DteSchemaRegistry $registro, DteSchemaValidator $validador): int
    {
        if ($this->option('validar') || $this->option('dte')) {
            return $this->validarDocumento($registro, $validador);
        }

        $filas = [];
        foreach ($registro->esquemas() as $clave => $datos) {
            $filas[] = [
                $clave,
                $datos['nombre'],
                $datos['tipo'],
                implode(', ', $registro->versiones($clave)),
                $registro->version($clave),
            ];
        }
        $this->table(['Clave', 'Nombre', 'Tipo', 'Versiones', 'En uso'], $filas);

        $problemas = $registro->problemasDeIntegridad();
        if ($problemas) {
            foreach ($problemas as $archivo => $motivo) {
                $this->error("$archivo: $motivo");
            }

            return self::FAILURE;
        }

        $this->info('Integridad: los '.count($filas).' esquemas coinciden con su huella sha256.');

        return self::SUCCESS;
    }

    private function validarDocumento(DteSchemaRegistry $registro, DteSchemaValidator $validador): int
    {
        if ($ruta = $this->option('validar')) {
            if (! is_file($ruta)) {
                $this->error("No existe el archivo: $ruta");

                return self::FAILURE;
            }
            $documento = json_decode((string) file_get_contents($ruta), true);
        } else {
            $json = DB::table('dtes')->where('id', (int) $this->option('dte'))->value('json_original');
            if (! $json) {
                $this->error('No existe el DTE '.$this->option('dte').'.');

                return self::FAILURE;
            }
            $documento = json_decode($json, true);
        }

        if (! is_array($documento)) {
            $this->error('El contenido no es un JSON válido.');

            return self::FAILURE;
        }

        $clave = $this->option('clave') ?: ($documento['identificacion']['tipoDte'] ?? null);
        if (! $clave || ! $registro->existe((string) $clave)) {
            $this->error('Indique --clave (el documento no trae un identificacion.tipoDte conocido).');

            return self::FAILURE;
        }

        $version = $this->option('esquema-version') !== null ? (int) $this->option('esquema-version') : null;
        $usada = $registro->version((string) $clave, $version);
        $errores = $validador->validar($documento, (string) $clave, $usada);

        if (! $errores) {
            $this->info("Válido contra {$registro->nombre((string) $clave)} (esquema v$usada).");

            return self::SUCCESS;
        }

        $this->error(count($errores)." error(es) contra {$registro->nombre((string) $clave)} (esquema v$usada):");
        foreach ($errores as $e) {
            $this->line("  {$e['ruta']}: {$e['mensaje']}");
        }

        return self::FAILURE;
    }
}
