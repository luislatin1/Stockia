<?php

namespace Stockia\DteSvMh\Services\Simulation;

use Stockia\DteSvMh\Models\Dte;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\Contracts\InvalidationInterface;

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
