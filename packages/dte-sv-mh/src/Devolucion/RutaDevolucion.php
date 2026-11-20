<?php

namespace Stockia\DteSvMh\Devolucion;

/**
 * Qué se le informa a Hacienda cuando se devuelve o ajusta una venta que ya tiene DTE.
 *
 * Los motivos son internos de Stockia: Hacienda no define una lista para la Nota de Crédito
 * (solo `observaciones`). Lo que sí define es el instrumento según el documento original
 * (Manual Funcional v2.0 §VII y §X):
 *  - CCF (03): Nota de Crédito para devoluciones y ajustes de valor; invalidación solo para
 *    errores del documento, con documento de reemplazo.
 *  - Factura (01): no admite NC. Devolución total = invalidación tipo 2 (rescindir); cambio de
 *    producto = invalidación tipo 3 con reemplazo; errores = tipo 1 con reemplazo. La devolución
 *    parcial (o la que sigue a un Evento de Retorno ya aplicado) se informa con Evento de Retorno.
 *    Una Factura con Evento de Retorno vigente no puede invalidarse (Manual §VII, nota 3).
 */
final class RutaDevolucion
{
    public const NOTA_CREDITO = 'nota_credito';

    public const INVALIDACION = 'invalidacion';

    public const EVENTO_RETORNO = 'evento_retorno';

    public const BLOQUEADO = 'bloqueado';

    public const NINGUNA = 'ninguna';

    public const ALCANCE_TOTAL = 'total';

    public const ALCANCE_PARCIAL = 'parcial';

    public const ALCANCE_VALOR = 'valor';

    /**
     * clave => [etiqueta, mueve_inventario, ajuste_de_valor]
     * Desperfecto y garantía no existen en la documentación de Hacienda: son clasificación interna.
     */
    public const MOTIVOS = [
        'devolucion' => ['Devolución de producto', true, false],
        'garantia' => ['Garantía / desperfecto', true, false],
        'cambio_producto' => ['Cambio de producto', true, false],
        'error_precio' => ['Error de precio', false, true],
        'descuento_omitido' => ['Descuento omitido', false, true],
        'error_fecha' => ['Error en la fecha de la operación', false, false],
        'error_datos_cliente' => ['Error en los datos del cliente', false, false],
    ];

    public static function etiqueta(string $motivo): string
    {
        return self::MOTIVOS[$motivo][0] ?? $motivo;
    }

    public static function esAjusteDeValor(string $motivo): bool
    {
        return self::MOTIVOS[$motivo][2] ?? false;
    }

    /**
     * @param  string|null  $tipoOriginal  tipo del DTE aceptado de la venta (01, 03, ...) o null si no tiene
     * @param  string  $alcance  total | parcial | valor
     * @return array{ruta:string, tipo_invalidacion:?string, requiere_reemplazo:bool, mensaje:?string}
     */
    public static function resolver(?string $tipoOriginal, string $motivo, string $alcance, bool $retornoActivo = false): array
    {
        if (! isset(self::MOTIVOS[$motivo])) {
            return self::bloqueo('Motivo de devolución no reconocido.');
        }
        if ($tipoOriginal === null) {
            return self::ruta(self::NINGUNA);
        }
        if (! in_array($tipoOriginal, ['01', '03'], true)) {
            return self::bloqueo("Los ajustes sobre documentos tipo {$tipoOriginal} aún no están soportados.");
        }

        $valor = self::esAjusteDeValor($motivo);
        $error = in_array($motivo, ['error_fecha', 'error_datos_cliente'], true);

        if ($tipoOriginal === '03') {
            if ($valor) {
                return $alcance === self::ALCANCE_VALOR
                    ? self::ruta(self::NOTA_CREDITO)
                    : self::bloqueo('Un ajuste de precio o descuento sobre un CCF se hace con "Ajustar valor" (Nota de Crédito), no anulando la venta.');
            }
            if ($error) {
                return $alcance === self::ALCANCE_TOTAL
                    ? self::ruta(self::INVALIDACION, '1', true)
                    : self::bloqueo('Un error de fecha o de datos del cliente se corrige invalidando el CCF completo y emitiendo uno nuevo: use "Anular".');
            }
            if ($alcance === self::ALCANCE_VALOR) {
                return self::bloqueo('Este motivo mueve producto: indique las cantidades devueltas.');
            }

            return self::ruta(self::NOTA_CREDITO);
        }

        // Factura (01): no admite Nota de Crédito.
        $devuelveBienes = in_array($motivo, ['devolucion', 'garantia', 'cambio_producto'], true);

        if ($devuelveBienes && ($alcance === self::ALCANCE_PARCIAL || $retornoActivo)) {
            return self::ruta(self::EVENTO_RETORNO);
        }
        if ($retornoActivo) {
            return self::bloqueo('La factura ya tiene un Evento de Retorno vigente: no puede invalidarse hasta invalidar ese evento (aún no disponible en el sistema).');
        }
        if ($alcance !== self::ALCANCE_TOTAL) {
            return self::bloqueo('Los errores de precio, descuento, fecha o datos de una Factura se corrigen invalidándola completa: use "Anular factura" con la venta que la reemplaza.');
        }

        return match (true) {
            $motivo === 'devolucion' || $motivo === 'garantia' => self::ruta(self::INVALIDACION, '2', false),
            $motivo === 'cambio_producto' => self::ruta(self::INVALIDACION, '3', true),
            default => self::ruta(self::INVALIDACION, '1', true),
        };
    }

    private static function ruta(string $ruta, ?string $tipo = null, bool $reemplazo = false): array
    {
        return ['ruta' => $ruta, 'tipo_invalidacion' => $tipo, 'requiere_reemplazo' => $reemplazo, 'mensaje' => null];
    }

    private static function bloqueo(string $mensaje): array
    {
        return ['ruta' => self::BLOQUEADO, 'tipo_invalidacion' => null, 'requiere_reemplazo' => false, 'mensaje' => $mensaje];
    }
}
