<?php

namespace Stockia\DteSvMh\Services\Real;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Stockia\DteSvMh\Models\DteSetting;

/**
 * Llamada autenticada al Sistema de Transmisión: cabecera Authorization con el token (que ya trae "Bearer"),
 * User-Agent y contenido JSON. Si Hacienda responde 401 se descarta el token guardado y se reintenta UNA vez
 * con uno nuevo (el token puede haber sido revocado antes de su vencimiento).
 *
 * @property \Stockia\DteSvMh\Services\Contracts\AuthInterface $authService
 */
trait HablaConHacienda
{
    protected function llamar(DteSetting $settings, string $metodo, string $url, ?array $cuerpo = null, int $espera = 15): Response
    {
        $respuesta = null;

        for ($i = 0; $i < 2; $i++) {
            $token = (string) ($this->authService->authenticate($settings)['body']['token'] ?? '');
            if ($token === '') {
                throw new \RuntimeException('No se obtuvo token de autenticación de Hacienda.');
            }

            $peticion = Http::withHeaders(['Authorization' => $token, 'User-Agent' => MhEndpoints::USER_AGENT])
                ->acceptJson()->timeout($espera);

            $respuesta = $metodo === 'GET' ? $peticion->get($url) : $peticion->asJson()->post($url, $cuerpo ?? []);

            if ($respuesta->status() === 401 && $i === 0) {
                $this->authService->olvidar($settings);

                continue;
            }

            return $respuesta;
        }

        return $respuesta;
    }

    protected function soloDigitos(?string $nit): string
    {
        return preg_replace('/\D+/', '', (string) $nit);
    }
}
