<?php

namespace App\Modules\Sales\Providers;

use App\Models\Module;
use App\Modules\Core\Providers\ModuleServiceProvider;

class SalesServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        if (Module::tableReady() && ! Module::isEnabled('sales')) {
            return;
        }

        parent::boot();
    }

    protected function routes(): array
    {
        return [
            'web' => [
                base_path('app/Modules/Sales/Routes/web.php'),
            ],
        ];
    }
}
