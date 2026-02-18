<?php

namespace App\Services\Dte\Real;

use App\Models\DteSetting;
use App\Services\Dte\Contracts\AuthInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RealAuthService implements AuthInterface
{
    public function authenticate(DteSetting $settings): array
    {
        $existing = DB::table('dte_tokens')
            ->where('company_id', $settings->company_id)
            ->where('ambiente', $settings->ambiente)
            ->where('expires_at', '>', now()->addMinute())
            ->latest('id')
            ->first();

        if ($existing) {
            DB::table('dte_tokens')
                ->where('id', $existing->id)
                ->update(['last_used_at' => now(), 'updated_at' => now()]);

            return [
                'status' => 'OK',
                'body' => [
                    'token' => (string) $existing->token,
                    'expiraEn' => max(60, now()->diffInSeconds($existing->expires_at)),
                ],
            ];
        }

        if (! $settings->auth_url) {
            throw new \RuntimeException('Modo real configurado sin auth_url.');
        }

        $auth = Http::timeout(15)->post($settings->auth_url, [
            'user' => $settings->api_user,
            'password' => $settings->api_password,
            'ambiente' => $settings->ambiente,
        ]);

        if (! $auth->successful()) {
            throw new \RuntimeException('No fue posible obtener token MH.');
        }

        $payload = $auth->json() ?: [];
        $token = (string) ($payload['token'] ?? $payload['access_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('Respuesta de autenticación sin token.');
        }

        $expiresIn = (int) ($payload['expires_in'] ?? 3600);
        $expiresAt = now()->addSeconds($expiresIn);

        DB::table('dte_tokens')->insert([
            'company_id' => $settings->company_id,
            'ambiente' => $settings->ambiente,
            'token' => $token,
            'expires_at' => $expiresAt,
            'last_used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'status' => 'OK',
            'body' => [
                'token' => $token,
                'expiraEn' => $expiresIn,
            ],
        ];
    }
}
