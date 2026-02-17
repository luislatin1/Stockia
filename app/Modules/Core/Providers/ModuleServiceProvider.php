<?php

namespace App\Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Return route groups keyed by route type.
     *
     * Example:
     * [
     *   'web' => [base_path('app/Modules/Foo/Routes/web.php')],
     *   'api' => [base_path('app/Modules/Foo/Routes/api.php')],
     * ]
     */
    protected function routes(): array
    {
        return [];
    }

    public function boot(): void
    {
        $routes = $this->routes();

        foreach ($routes['web'] ?? [] as $webRouteFile) {
            if (is_file($webRouteFile)) {
                Route::middleware('web')->group($webRouteFile);
            }
        }

        foreach ($routes['api'] ?? [] as $apiRouteFile) {
            if (is_file($apiRouteFile)) {
                Route::middleware('api')->group($apiRouteFile);
            }
        }
    }
}

