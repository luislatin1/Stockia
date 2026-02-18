<?php

namespace App\Services\Dte\Real;

use App\Models\Dte;
use App\Models\DteSetting;
use App\Services\Dte\Contracts\AuthInterface;
use App\Services\Dte\Contracts\InvalidationInterface;
use Illuminate\Support\Facades\Http;

class RealInvalidationService implements InvalidationInterface
{
    public function __construct(private readonly AuthInterface $authService)
    {
    }

    public function invalidate(Dte $dte, DteSetting $settings, array $payload = []): array
    {
        if (! $settings->send_url) {
            throw new \RuntimeException('Modo real configurado sin endpoint para invalidación.');
        }

        $auth = $this->authService->authenticate($settings);
        $token = (string) ($auth['body']['token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('Token inválido para invalidación real.');
        }

        $body = [
            'codigoGeneracion' => $dte->codigo_generacion,
            'numeroControl' => $dte->numero_control,
            'tipoDte' => $dte->tipo_dte,
            'motivo' => $payload['motivo'] ?? null,
            'tipoInvalidacion' => $payload['tipo_invalidacion'] ?? null,
        ];

        $response = Http::timeout(20)
            ->withToken($token)
            ->post($settings->send_url, $body);

        if (! $response->successful()) {
            throw new \RuntimeException('Falló invalidación en modo real.');
        }

        return $response->json() ?: ['estado' => 'INVALIDADO'];
    }
}
