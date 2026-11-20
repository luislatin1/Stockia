<?php

namespace Stockia\DteSvMh\Devolucion;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Plazo para transmitir el Evento de Invalidación (Manual Funcional v2.0 §VII):
 *  - Factura (01), Exportación, Sujeto Excluido: 3 meses desde el sello de recepción.
 *  - CCF (03), NC, ND y demás: hasta las 23:59:59 del décimo día hábil del mes siguiente al
 *    período tributario en que el documento obtuvo el sello.
 * Día hábil = lunes a viernes. NO se descuentan feriados (el manual no los lista): el sistema
 * puede aceptar un día más de lo legal; ante la duda, invalide antes.
 */
final class PlazoInvalidacion
{
    public static function limite(string $tipoDte, CarbonInterface $fechaSello, string $zona = 'America/El_Salvador'): CarbonImmutable
    {
        $f = CarbonImmutable::instance($fechaSello)->setTimezone($zona);

        if (in_array($tipoDte, ['01', '11', '14'], true)) {
            return $f->addMonthsNoOverflow(3)->endOfDay();
        }

        $d = $f->addMonthNoOverflow()->startOfMonth();
        $habiles = 0;
        while (true) {
            if ($d->isWeekday()) {
                $habiles++;
                if ($habiles === 10) {
                    return $d->endOfDay();
                }
            }
            $d = $d->addDay();
        }
    }

    public static function vigente(string $tipoDte, CarbonInterface $fechaSello, ?CarbonInterface $ahora = null, string $zona = 'America/El_Salvador'): bool
    {
        $ahora = CarbonImmutable::instance($ahora ?? now())->setTimezone($zona);

        return $ahora->lessThanOrEqualTo(self::limite($tipoDte, $fechaSello, $zona));
    }
}
