<?php

namespace Stockia\DteSvMh\Services\Simulation;

use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\Contracts\AuthInterface;

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

    public function olvidar(DteSetting $settings): void
    {
    }
}
