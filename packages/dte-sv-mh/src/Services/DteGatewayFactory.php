<?php

namespace Stockia\DteSvMh\Services;

use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\Contracts\AuthInterface;
use Stockia\DteSvMh\Services\Contracts\InvalidationInterface;
use Stockia\DteSvMh\Services\Contracts\SendInterface;
use Stockia\DteSvMh\Services\Contracts\SignerInterface;
use Stockia\DteSvMh\Services\Real\RealAuthService;
use Stockia\DteSvMh\Services\Real\RealInvalidationService;
use Stockia\DteSvMh\Services\Real\RealSendService;
use Stockia\DteSvMh\Services\Real\RealSigner;
use Stockia\DteSvMh\Services\Simulation\FakeAuthService;
use Stockia\DteSvMh\Services\Simulation\FakeInvalidationService;
use Stockia\DteSvMh\Services\Simulation\FakeSendService;
use Stockia\DteSvMh\Services\Simulation\FakeSigner;

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
