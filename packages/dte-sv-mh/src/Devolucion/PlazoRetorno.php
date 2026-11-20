<?php

namespace Stockia\DteSvMh\Devolucion;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Plazo del Evento de Retorno sobre una Factura (Manual Funcional v2.0 §X).
 * Tres meses desde la FECHA DE GENERACIÓN del DTE; dos años si el emisor tiene alguna de las
 * actividades 21001, 21008, 46482, 46484, 46491 o 47721.
 * Nota: el mismo manual dice en otro párrafo "tres meses desde el sello de recepción". Se usa la
 * fecha de generación (la más estricta cuando difieren) y es lo que se declaró en el DTE.
 */
final class PlazoRetorno
{
    public const ACTIVIDADES_DOS_ANIOS = ['21001', '21008', '46482', '46484', '46491', '47721'];

    public static function limite(string $fechaGeneracion, ?string $codActividadEmisor, string $zona = 'America/El_Salvador'): CarbonImmutable
    {
        $f = CarbonImmutable::parse($fechaGeneracion, $zona);

        return in_array((string) $codActividadEmisor, self::ACTIVIDADES_DOS_ANIOS, true)
            ? $f->addYears(2)->endOfDay()
            : $f->addMonthsNoOverflow(3)->endOfDay();
    }

    public static function vigente(string $fechaGeneracion, ?string $codActividadEmisor, ?CarbonInterface $ahora = null, string $zona = 'America/El_Salvador'): bool
    {
        $ahora = CarbonImmutable::instance($ahora ?? now())->setTimezone($zona);

        return $ahora->lessThanOrEqualTo(self::limite($fechaGeneracion, $codActividadEmisor, $zona));
    }
}
