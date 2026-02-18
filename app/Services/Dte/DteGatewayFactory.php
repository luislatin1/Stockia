<?php

namespace App\Services\Dte;

use App\Models\DteSetting;
use App\Services\Dte\Contracts\AuthInterface;
use App\Services\Dte\Contracts\InvalidationInterface;
use App\Services\Dte\Contracts\SendInterface;
use App\Services\Dte\Contracts\SignerInterface;
use App\Services\Dte\Real\RealAuthService;
use App\Services\Dte\Real\RealInvalidationService;
use App\Services\Dte\Real\RealSendService;
use App\Services\Dte\Real\RealSigner;
use App\Services\Dte\Simulation\FakeAuthService;
use App\Services\Dte\Simulation\FakeInvalidationService;
use App\Services\Dte\Simulation\FakeSendService;
use App\Services\Dte\Simulation\FakeSigner;

class DteGatewayFactory
{
    public function auth(DteSetting $settings, ?string $forcedMode = null): AuthInterface
    {
        return $this->mode($settings, $forcedMode) === 'real'
            ? new RealAuthService()
            : new FakeAuthService();
    }

    public function signer(DteSetting $settings, ?string $forcedMode = null): SignerInterface
    {
        return $this->mode($settings, $forcedMode) === 'real'
            ? new RealSigner()
            : new FakeSigner();
    }

    public function sender(DteSetting $settings, ?string $forcedMode = null): SendInterface
    {
        $mode = $this->mode($settings, $forcedMode);
        if ($mode === 'real') {
            $auth = $this->auth($settings, 'real');
            return new RealSendService($auth);
        }

        return new FakeSendService();
    }

    public function invalidation(DteSetting $settings, ?string $forcedMode = null): InvalidationInterface
    {
        $mode = $this->mode($settings, $forcedMode);
        if ($mode === 'real') {
            $auth = $this->auth($settings, 'real');
            return new RealInvalidationService($auth);
        }

        return new FakeInvalidationService();
    }

    public function mode(DteSetting $settings, ?string $forcedMode = null): string
    {
        $mode = $forcedMode ?: ((string) ($settings->integration_mode ?: config('dte.mode', 'simulacion')));
        $mode = strtolower(trim($mode));

        if ($mode === 'static') {
            $mode = 'simulacion';
        }

        if (! in_array($mode, ['simulacion', 'real', 'contingencia'], true)) {
            $mode = 'simulacion';
        }

        return $mode;
    }
}
