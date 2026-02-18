<?php

namespace App\Services\Dte\Simulation;

use App\Models\DteSetting;
use App\Services\SimuladorMhValidator;
use App\Services\Dte\Contracts\SendInterface;

class FakeSendService implements SendInterface
{
    public function send(array $signedDte, DteSetting $settings): array
    {
        $payload = $signedDte['payload'] ?? [];
        $codigoGeneracion = $payload['identificacion']['codigoGeneracion'] ?? null;
        $simulated = SimuladorMhValidator::validar($payload);

        if (($simulated['estado'] ?? 'RECHAZADO') !== 'PROCESADO') {
            return [
                'status' => 'RECHAZADO',
                'sello' => null,
                'response' => [
                    'version' => 1,
                    'ambiente' => $settings->ambiente,
                    'estado' => 'RECHAZADO',
                    'codigoGeneracion' => $codigoGeneracion,
                    'selloRecibido' => null,
                    'fhProcesamiento' => $simulated['fhProcesamiento'] ?? now()->toDateTimeString(),
                    'observaciones' => $simulated['observaciones'] ?? ['Documento rechazado en simulador'],
                ],
                'error' => implode(' | ', $simulated['observaciones'] ?? ['Documento rechazado en simulador']),
            ];
        }

        $response = $settings->static_response ?: [
            'version' => 1,
            'ambiente' => $settings->ambiente,
            'estado' => 'PROCESADO',
            'codigoGeneracion' => $codigoGeneracion,
            'selloRecibido' => (string) ($settings->static_sello ?: ($simulated['selloRecibido'] ?? strtoupper(uniqid('SELLO_')))),
            'fhProcesamiento' => $simulated['fhProcesamiento'] ?? now()->toDateTimeString(),
            'observaciones' => $simulated['observaciones'] ?? [],
        ];

        return [
            'status' => (($settings->static_estado ?: 'ACEPTADO') === 'ACEPTADO') ? 'ACEPTADO' : (string) ($settings->static_estado ?: 'ACEPTADO'),
            'sello' => $response['selloRecibido'] ?? null,
            'response' => $response,
            'error' => null,
        ];
    }
}
