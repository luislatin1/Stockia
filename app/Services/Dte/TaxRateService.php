<?php

namespace App\Services\Dte;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consulta de tasas de impuesto vigentes en una fecha (tabla `tax_rates`).
 * Si la tabla aún no existe (instalación sin migrar) o no hay fila vigente,
 * usa el valor de respaldo para no detener las ventas.
 */
class TaxRateService
{
    public const IVA = 'IVA';

    public const RET_IVA = 'RET_IVA';

    public const PERC_IVA = 'PERC_IVA';

    public const PAGO_CUENTA = 'PAGO_CUENTA';

    /** Respaldo en porcentaje, solo si la tabla no responde. */
    private const FALLBACK = [
        self::IVA => 13.0,
        self::RET_IVA => 1.0,
        self::PERC_IVA => 1.0,
        self::PAGO_CUENTA => 1.75,
    ];

    /** @var array<string, float> */
    private array $cache = [];

    private ?bool $tableExists = null;

    /** Tasa como fracción (13 % → 0.13) vigente en $fecha (hoy si es null). */
    public function rate(string $codigo, ?CarbonInterface $fecha = null): float
    {
        return $this->percent($codigo, $fecha) / 100;
    }

    /** Tasa en porcentaje (13 % → 13.0). */
    public function percent(string $codigo, ?CarbonInterface $fecha = null): float
    {
        $dia = ($fecha ?? now())
            ->copy()
            ->setTimezone(config('dte.timezone', 'America/El_Salvador'))
            ->toDateString();

        $key = $codigo.'|'.$dia;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $value = $this->fromTable($codigo, $dia);

        if ($value === null) {
            if (! isset(self::FALLBACK[$codigo])) {
                throw new \RuntimeException("No hay tasa vigente para '{$codigo}' en {$dia}.");
            }
            $value = self::FALLBACK[$codigo];
        }

        return $this->cache[$key] = $value;
    }

    private function fromTable(string $codigo, string $dia): ?float
    {
        $this->tableExists ??= Schema::hasTable('tax_rates');
        if (! $this->tableExists) {
            return null;
        }

        $row = DB::table('tax_rates')
            ->where('codigo', $codigo)
            ->where('vigente_desde', '<=', $dia)
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $dia))
            ->orderByDesc('vigente_desde')
            ->first();

        return $row ? (float) $row->tasa : null;
    }
}
