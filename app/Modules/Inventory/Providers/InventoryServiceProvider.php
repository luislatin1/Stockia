<?php

namespace App\Modules\Inventory\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        if (Schema::hasTable('modules')) {
            $module = DB::table('modules')->where('key', 'inventory')->first();

            if ($module && ! (bool) $module->enabled) {
                return;
            }
        }

        parent::boot();
    }

    protected function routes(): array
    {
        return [
            'web' => [
                base_path('app/Modules/Inventory/Routes/web.php'),
            ],
        ];
    }
}

