<?php

namespace App\Services;

class SimuladorMhValidator
{
    public static function validar(array $dte): array
    {
        $errores = [];

        $cuerpo = $dte['cuerpoDocumento'] ?? [];
        $resumen = $dte['resumen'] ?? [];

        $sumaGravada = 0.0;
        foreach ($cuerpo as $item) {
            $sumaGravada += (float) ($item['ventaGravada'] ?? 0);
        }

        if (round($sumaGravada, 2) !== round((float) ($resumen['totalGravada'] ?? 0), 2)) {
            $errores[] = 'Total gravada no coincide con detalle.';
        }

        if ((float) ($resumen['totalPagar'] ?? 0) <= 0) {
            $errores[] = 'Total pagar inválido.';
        }

        if (! empty($errores)) {
            return [
                'estado' => 'RECHAZADO',
                'observaciones' => $errores,
            ];
        }

        return [
            'estado' => 'PROCESADO',
            'selloRecibido' => strtoupper(uniqid('SELLO_')),
            'fhProcesamiento' => now()->toDateTimeString(),
            'observaciones' => [],
        ];
    }
}
