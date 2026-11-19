<?php

namespace Stockia\DteSvMh\Schemas;

/** El documento no cumple el esquema oficial: no debe firmarse ni transmitirse. */
class DteSchemaException extends \RuntimeException
{
    /**
     * @param  array<int, array{ruta:string, mensaje:string}>  $errores
     */
    public function __construct(
        public readonly string $clave,
        public readonly int $version,
        public readonly array $errores,
    ) {
        $lineas = array_map(fn (array $e) => ' - '.$e['ruta'].': '.$e['mensaje'], array_slice($errores, 0, 15));
        $extra = count($errores) > 15 ? "\n ... y ".(count($errores) - 15).' más' : '';

        parent::__construct(
            "El documento no cumple el esquema oficial '{$clave}' v{$version} (".count($errores)." error(es)):\n"
            .implode("\n", $lineas).$extra
        );
    }
}
