<?php

namespace App\Providers;

use App\Models\Company;
use App\Policies\CompanyPolicy;
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
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
