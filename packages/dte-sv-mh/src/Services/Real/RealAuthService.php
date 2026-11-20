<?php

namespace Stockia\DteSvMh\Services\Real;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\Contracts\AuthInterface;

/**
 * Autenticación contra la API de Seguridad (Manual Técnico §1.1 y §4.1).
 * POST {servidor}/seguridad/auth, application/x-www-form-urlencoded, campos `user` y `pwd`.
 * Éxito: {status:"OK", body:{token:"Bearer eyJ…"}}. El token ya trae el prefijo "Bearer" y se envía tal cual en
 * `Authorization`. Dura 48 h en pruebas y 24 h en producción: se guarda y se reutiliza (no se autentica por envío).
 * La contraseña (13–25 caracteres) nunca se registra en logs ni en respuestas.
 */
class RealAuthService implements AuthInterface
{
    /** Margen antes del vencimiento para pedir un token nuevo. */
    private const MARGEN_SEGUNDOS = 3600;

    public function authenticate(DteSetting $settings): array
    {
        $url = MhEndpoints::auth($settings); // también valida la separación de ambientes

        $vigente = DB::table('dte_tokens')
            ->where('company_id', $settings->company_id)
            ->where('ambiente', $settings->ambiente)
            ->where('expires_at', '>', now()->addSeconds(self::MARGEN_SEGUNDOS))
            ->latest('id')->first();

        if ($vigente) {
            DB::table('dte_tokens')->where('id', $vigente->id)->update(['last_used_at' => now(), 'updated_at' => now()]);

            return ['status' => 'OK', 'body' => ['token' => (string) $vigente->token, 'expiraEn' => max(60, now()->diffInSeconds($vigente->expires_at))]];
        }

        if (! $settings->api_user || ! $settings->api_password) {
            throw new \RuntimeException('Configure el usuario y la contraseña de la API de Hacienda en DTE > Administración (modo real).');
        }

        try {
            $resp = Http::asForm()
                ->withHeaders(['User-Agent' => MhEndpoints::USER_AGENT])
                ->timeout(15)
                ->post($url, ['user' => $settings->api_user, 'pwd' => $settings->api_password]);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('No fue posible conectar con el servicio de autenticación de Hacienda.');
        }

        $json = $resp->json() ?: [];
        $token = (string) ($json['body']['token'] ?? '');
        if (! $resp->successful() || strtoupper((string) ($json['status'] ?? '')) !== 'OK' || $token === '') {
            $detalle = $json['message'] ?? $json['body']['mensaje'] ?? $json['error'] ?? 'credenciales rechazadas';
            throw new \RuntimeException('Hacienda rechazó la autenticación: '.(is_array($detalle) ? implode(' | ', $detalle) : $detalle).'.');
        }

        if (! str_starts_with($token, 'Bearer ')) {
            $token = 'Bearer '.$token;
        }

        $vigencia = (string) $settings->ambiente === '01' ? 24 * 3600 : 48 * 3600;
        DB::table('dte_tokens')->where('company_id', $settings->company_id)->where('ambiente', $settings->ambiente)->delete();
        DB::table('dte_tokens')->insert([
            'company_id' => $settings->company_id,
            'ambiente' => $settings->ambiente,
            'token' => $token,
            'expires_at' => now()->addSeconds($vigencia),
            'last_used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['status' => 'OK', 'body' => ['token' => $token, 'expiraEn' => $vigencia]];
    }

    public function olvidar(DteSetting $settings): void
    {
        DB::table('dte_tokens')->where('company_id', $settings->company_id)->where('ambiente', $settings->ambiente)->delete();
    }
}
