<?php

namespace App\Services\Dte;

use App\Models\DteCorrelative;
use Illuminate\Support\Facades\DB;

class DteCorrelativeService
{
    public function next(int $companyId, string $tipoDte, string $establecimiento, string $puntoVenta): array
    {
        return DB::transaction(function () use ($companyId, $tipoDte, $establecimiento, $puntoVenta) {
            $row = DteCorrelative::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'tipo_dte' => $tipoDte,
                        'establecimiento' => $establecimiento,
                        'punto_venta' => $puntoVenta,
                    ],
                    ['correlativo_actual' => 0]
                );

            $row->correlativo_actual = (int) $row->correlativo_actual + 1;
            $row->save();

            $correlativo = (int) $row->correlativo_actual;

            return [
                'correlativo' => $correlativo,
                'numero_control' => sprintf(
                    'DTE-%s-%s-%s-%015d',
                    $tipoDte,
                    $establecimiento,
                    $puntoVenta,
                    $correlativo
                ),
            ];
        });
    }
}
