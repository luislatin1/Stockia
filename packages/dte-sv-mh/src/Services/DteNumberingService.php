<?php

namespace Stockia\DteSvMh\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Stockia\DteSvMh\Models\DteEstablecimiento;
use Stockia\DteSvMh\Models\DtePuntoVenta;

/**
 * Número de control: DTE-<tipo>-<estab><pv>-<consecutivo de 15 dígitos>.
 *
 * Reglas (Normativa 7.1.2 y Anexo I 1.3):
 *  - El consecutivo es centralizado (uno por empresa) o por establecimiento.
 *  - NUNCA por punto de venta ni por tipo de documento.
 *  - Se reinicia en 1 con el primer DTE de cada ejercicio impositivo.
 *  - Va de 1 a 999999999999999.
 */
class DteNumberingService
{
    public const MODO_CENTRALIZADA = 'centralizada';

    public const MODO_POR_ESTABLECIMIENTO = 'por_establecimiento';

    private const MAXIMO = 999999999999999;

    /**
     * @return array{consecutivo:int, numero_control:string, ejercicio:int}
     */
    public function next(
        int $companyId,
        DteEstablecimiento $establecimiento,
        DtePuntoVenta $puntoVenta,
        string $tipoDte,
        string $modo = self::MODO_CENTRALIZADA,
        ?CarbonInterface $fecha = null,
    ): array {
        if (! preg_match(DteEstablecimiento::CODIGO_REGEX, $establecimiento->codigo)
            || ! preg_match(DtePuntoVenta::CODIGO_REGEX, $puntoVenta->codigo)) {
            throw new \InvalidArgumentException(
                "Código de establecimiento '{$establecimiento->codigo}' o punto de venta '{$puntoVenta->codigo}' inválido."
            );
        }

        if (! preg_match('/^[0-9]{2}$/', $tipoDte)) {
            throw new \InvalidArgumentException("Tipo de DTE inválido: '{$tipoDte}'.");
        }

        if ((int) $puntoVenta->establecimiento_id !== (int) $establecimiento->id) {
            throw new \InvalidArgumentException('El punto de venta no pertenece al establecimiento indicado.');
        }

        $scopeKey = $modo === self::MODO_POR_ESTABLECIMIENTO ? 'E'.$establecimiento->id : 'C';
        $ejercicio = $this->ejercicio($fecha);

        $consecutivo = DB::transaction(function () use ($companyId, $scopeKey, $ejercicio) {
            $key = ['company_id' => $companyId, 'scope_key' => $scopeKey, 'ejercicio' => $ejercicio];

            // La fila debe existir antes de bloquearla: sin esto, dos procesos
            // simultáneos del primer DTE del año no tendrían nada que bloquear.
            DB::table('dte_numeraciones')->insertOrIgnore($key + [
                'ultimo' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('dte_numeraciones')->where($key)->lockForUpdate()->first();
            $siguiente = (int) $row->ultimo + 1;

            if ($siguiente > self::MAXIMO) {
                throw new \RuntimeException('Se agotó el consecutivo del número de control.');
            }

            DB::table('dte_numeraciones')->where('id', $row->id)
                ->update(['ultimo' => $siguiente, 'updated_at' => now()]);

            return $siguiente;
        });

        return [
            'consecutivo' => $consecutivo,
            'ejercicio' => $ejercicio,
            'numero_control' => sprintf(
                'DTE-%s-%s%s-%015d',
                $tipoDte,
                $establecimiento->codigo,
                $puntoVenta->codigo,
                $consecutivo
            ),
        ];
    }

    /** Ejercicio impositivo = año calendario, medido en hora de El Salvador (no UTC). */
    public function ejercicio(?CarbonInterface $fecha = null): int
    {
        $fecha = ($fecha ?? now())->copy()->setTimezone(config('dte.timezone', 'America/El_Salvador'));

        return (int) $fecha->format('Y');
    }
}
