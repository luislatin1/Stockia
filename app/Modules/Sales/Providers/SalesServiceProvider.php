<?php

namespace App\Modules\Sales\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        if (Schema::hasTable('modules')) {
            $module = DB::table('modules')->where('key', 'sales')->first();

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
                base_path('app/Modules/Sales/Routes/web.php'),
            ],
        ];
    }
}
