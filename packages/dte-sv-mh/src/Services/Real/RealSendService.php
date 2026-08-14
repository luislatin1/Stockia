<?php

namespace Stockia\DteSvMh\Services\Real;

use Illuminate\Support\Facades\Http;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\Contracts\AuthInterface;
use Stockia\DteSvMh\Services\Contracts\SendInterface;

class RealSendService implements SendInterface
{
    public function __construct(private readonly AuthInterface $authService)
    {
    }

    public function send(array $signedDte, DteSetting $settings): array
    {
        if (! $settings->send_url) {
            throw new \RuntimeException('Modo real configurado sin send_url.');
        }

        $auth = $this->authService->authenticate($settings);
        $token = (string) ($auth['body']['token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('Token inválido para envío real.');
        }

        $response = Http::timeout(25)
            ->withToken($token)
            ->post($settings->send_url, $signedDte);

        $json = $response->json() ?: ['raw' => $response->body()];
        $ok = $response->successful();
        $estado = (string) ($json['estado'] ?? ($ok ? 'ACEPTADO' : 'RECHAZADO'));

        return [
            'status' => $estado === 'PROCESADO' ? 'ACEPTADO' : $estado,
            'sello' => $json['selloRecibido'] ?? $json['selloRecepcion'] ?? null,
            'response' => $json,
            'error' => $ok ? null : 'Error en envío real a MH',
        ];
    }
}
