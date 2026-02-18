<?php

namespace App\Services\Dte\Simulation;

use App\Models\DteSetting;
use App\Services\Dte\Contracts\AuthInterface;

class FakeAuthService implements AuthInterface
{
    public function authenticate(DteSetting $settings): array
    {
        return [
            'status' => 'OK',
            'body' => [
                'token' => (string) ($settings->static_token ?: ('TOKEN_SIMULADO_' . uniqid())),
                'expiraEn' => 3600,
            ],
        ];
    }
}
