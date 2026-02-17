# Modular Architecture (Initial Setup)

This project now supports a modular monolith structure under `app/Modules`.

## Current module example

- `Sales`
  - `Providers/SalesServiceProvider.php`
  - `Routes/web.php`
  - `Http/Controllers/SaleController.php` (transitional wrapper)
- `Inventory`
  - `Providers/InventoryServiceProvider.php`
  - `Routes/web.php`
  - `Http/Controllers/InventoryMovementController.php` (transitional wrapper)
  - `Http/Controllers/WarehouseController.php` (transitional wrapper)

## How modules are loaded

1. Each module has a Service Provider.
2. Providers are registered in `bootstrap/providers.php`.
3. Module providers load their own route files.

## Creating a new module

1. Create folders:
   - `app/Modules/<ModuleName>/Providers`
   - `app/Modules/<ModuleName>/Routes`
   - `app/Modules/<ModuleName>/Http/Controllers`
   - (optional) `Actions`, `Models`, `Policies`, `Resources/views`, `Tests`
2. Add `<ModuleName>ServiceProvider` extending:
   - `App\Modules\Core\Providers\ModuleServiceProvider`
3. Register provider in:
   - `bootstrap/providers.php`
4. Put module routes in:
   - `app/Modules/<ModuleName>/Routes/web.php`

## Installing external plugins (ZIP + CLI)

1. Export plugin as ZIP from its repository.
2. In target project, run:
   - `php artisan modules:install-zip path\to\plugin.zip`
3. Then:
   - `composer update vendor/package-name`
   - `php artisan migrate`
4. Activate the module in **Configuración > Panel de Control**.

## Suggested next step

Move actual sales business logic from `App\Http\Controllers\SaleController`
to module actions/classes under `app/Modules/Sales`.
