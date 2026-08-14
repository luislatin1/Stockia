<?php

namespace App\Providers;

use App\Models\Company;
use App\Policies\CompanyPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
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

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $mode = strtolower((string) config('dte.mode', 'simulacion'));
        if ($mode === 'static') {
            $mode = 'simulacion';
        }

        $this->app->bind(AuthInterface::class, function () use ($mode) {
            return $mode === 'real' ? new RealAuthService() : new FakeAuthService();
        });

        $this->app->bind(SignerInterface::class, function () use ($mode) {
            return $mode === 'real' ? new RealSigner() : new FakeSigner();
        });

        $this->app->bind(SendInterface::class, function () use ($mode) {
            if ($mode === 'real') {
                return new RealSendService(app(AuthInterface::class));
            }

            return new FakeSendService();
        });

        $this->app->bind(InvalidationInterface::class, function () use ($mode) {
            if ($mode === 'real') {
                return new RealInvalidationService(app(AuthInterface::class));
            }

            return new FakeInvalidationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
        protected $policies = [
        Company::class => CompanyPolicy::class,
    ];
}
