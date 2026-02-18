<?php

namespace App\Services\Dte\Simulation;

use App\Models\Dte;
use App\Models\DteSetting;
use App\Services\Dte\Contracts\InvalidationInterface;

class FakeInvalidationService implements InvalidationInterface
{
    public function invalidate(Dte $dte, DteSetting $settings, array $payload = []): array
    {
        return [
            'estado' => 'INVALIDADO',
            'codigoGeneracion' => $dte->codigo_generacion,
            'selloRecibido' => strtoupper(uniqid('SELLO_INV_')),
            'fhProcesamiento' => now()->toDateTimeString(),
            'observaciones' => [],
        ];
    }
}
