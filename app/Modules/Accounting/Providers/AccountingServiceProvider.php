<?php

namespace App\Modules\Accounting\Providers;

use App\Models\Module;
use App\Modules\Core\Providers\ModuleServiceProvider;

class AccountingServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        if (Module::tableReady() && ! Module::isEnabled('accounting')) {
            return;
        }

        parent::boot();
    }

    protected function routes(): array
    {
        return [
            'web' => [
                base_path('app/Modules/Accounting/Routes/web.php'),
            ],
        ];
    }
}
