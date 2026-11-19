<?php

namespace Stockia\DteSvMh\Schemas;

use Carbon\CarbonInterface;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

/**
 * Valida un DTE o evento contra el esquema oficial ANTES de firmarlo.
 *
 * Aviso del Manual Técnico (6.1): los esquemas son una guía estructural; cumplirlos
 * no exime de las validaciones lógicas y de cálculo de la Normativa (Anexo IV), que
 * Hacienda también ejecuta. Esta validación detecta lo estructural: campos que
 * faltan o sobran, tipos, longitudes, patrones (número de control, código de
 * generación), catálogos cerrados y la versión/tipo del documento.
 */
class DteSchemaValidator
{
    private const MAX_ERRORES = 100;

    /** @var array<string, object> esquemas ya decodificados, por ruta */
    private array $esquemas = [];

    public function __construct(private readonly DteSchemaRegistry $registro)
    {
    }

    public function registro(): DteSchemaRegistry
    {
        return $this->registro;
    }

    /**
     * @param  array|object  $documento  el DTE tal como se firmaría
     * @return array<int, array{ruta:string, mensaje:string}> vacío si es válido
     */
    public function validar(array|object $documento, string $clave, ?int $version = null, ?CarbonInterface $fecha = null): array
    {
        $version = $this->registro->version($clave, $version, $fecha);
        $esquema = $this->esquema($this->registro->ruta($clave, $version));

        $validador = new Validator();
        $validador->setMaxErrors(self::MAX_ERRORES);
        // Por defecto se detiene en el primer fallo de cada objeto; queremos el informe completo.
        $validador->setStopAtFirstError(false);
        $resultado = $validador->validate($this->comoObjeto($documento), $esquema);

        if ($resultado->isValid()) {
            return [];
        }

        return $this->aplanar($resultado->error());
    }

    /** @throws DteSchemaException */
    public function asegurar(array|object $documento, string $clave, ?int $version = null, ?CarbonInterface $fecha = null): void
    {
        $version = $this->registro->version($clave, $version, $fecha);
        $errores = $this->validar($documento, $clave, $version, $fecha);

        if ($errores) {
            throw new DteSchemaException($clave, $version, $errores);
        }
    }

    /**
     * El validador distingue objeto de arreglo JSON; un array asociativo de PHP debe
     * convertirse a objeto. JSON_PRESERVE_ZERO_FRACTION mantiene 100.0 como número
     * decimal (no como entero) para que el tipo enviado sea el que se valida.
     */
    private function comoObjeto(array|object $documento): mixed
    {
        // [] vacío es un objeto JSON vacío para un DTE (PHP no distingue); si no, saldría como arreglo.
        if ($documento === []) {
            $documento = new \stdClass();
        }

        return json_decode(
            json_encode($documento, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    private function esquema(string $ruta): object
    {
        return $this->esquemas[$ruta] ??= json_decode(
            (string) file_get_contents($ruta),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /** @return array<int, array{ruta:string, mensaje:string}> */
    private function aplanar(ValidationError $error): array
    {
        $formateador = new ErrorFormatter();
        $salida = [];

        $listas = $formateador->formatFlat($error, fn (ValidationError $e) => $this->items($e, $formateador));

        foreach ($listas as $lista) {
            foreach ($lista as $item) {
                // Sin duplicados: un mismo campo puede fallar por varias ramas de oneOf/allOf.
                $salida[$item['ruta'].'|'.$item['mensaje']] = $item;
            }
        }

        return array_values($salida);
    }

    /**
     * Convierte un error del validador en uno o más errores legibles, en español.
     * Los envoltorios ("los campos deben cumplir el esquema") se omiten: el detalle
     * está en los errores hijo.
     *
     * @return array<int, array{ruta:string, mensaje:string}>
     */
    private function items(ValidationError $e, ErrorFormatter $formateador): array
    {
        $base = '/'.implode('/', $e->data()->fullPath());
        $base = $base === '/' ? '' : $base;
        $args = $e->args();
        $una = fn (string $mensaje) => [['ruta' => $base === '' ? '/' : $base, 'mensaje' => $mensaje]];

        return match ($e->keyword()) {
            '', 'properties', 'items', 'patternProperties', 'allOf', 'dependencies' => [],
            'required' => array_map(
                fn (string $campo) => ['ruta' => $base.'/'.$campo, 'mensaje' => 'campo requerido: falta'],
                (array) ($args['missing'] ?? [])
            ),
            'additionalProperties' => array_map(
                fn (string $campo) => ['ruta' => $base.'/'.$campo, 'mensaje' => 'campo no permitido por el esquema'],
                (array) ($args['properties'] ?? [])
            ),
            'type' => $una('debe ser de tipo '.$this->listar($args['expected'] ?? '?').' (se envió '.($args['type'] ?? '?').')'),
            'const' => $una('debe ser exactamente '.json_encode($args['const'] ?? null, JSON_UNESCAPED_UNICODE)),
            'enum' => $una('no es uno de los valores permitidos'),
            'minLength' => $una('longitud mínima '.($args['min'] ?? '?').' (tiene '.($args['length'] ?? '?').')'),
            'maxLength' => $una('longitud máxima '.($args['max'] ?? '?').' (tiene '.($args['length'] ?? '?').')'),
            'minimum' => $una('debe ser mayor o igual a '.($args['min'] ?? '?')),
            'maximum' => $una('debe ser menor o igual a '.($args['max'] ?? '?')),
            'minItems' => $una('debe tener al menos '.($args['min'] ?? '?').' elemento(s)'),
            'maxItems' => $una('debe tener como máximo '.($args['max'] ?? '?').' elemento(s)'),
            'format' => $una(($args['format'] ?? '') === 'date'
                ? 'no es una fecha válida (AAAA-MM-DD)'
                : "formato inválido ('".($args['format'] ?? '?')."')"),
            'pattern' => $una('no cumple el formato esperado: '.$this->recortar((string) ($args['pattern'] ?? ''))),
            default => $una($formateador->formatErrorMessage($e)),
        };
    }

    private function listar(array|string $valor): string
    {
        return implode(' o ', (array) $valor);
    }

    private function recortar(string $texto): string
    {
        $texto = str_replace(["
", "
", "

"], '', $texto);

        return mb_strlen($texto) > 90 ? mb_substr($texto, 0, 90).'…' : $texto;
    }
}
