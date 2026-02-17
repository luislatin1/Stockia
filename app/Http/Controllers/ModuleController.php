<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Services\PluginInstaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    private const CATALOG = [
        'sales' => [
            'name' => 'Ventas',
            'description' => 'Gestiona ventas, cobros en efectivo, tickets y facturas locales.',
            'version' => '1.0.0',
            'dependencies' => ['inventory'],
            'provider' => \App\Modules\Sales\Providers\SalesServiceProvider::class,
        ],
        'inventory' => [
            'name' => 'Inventario',
            'description' => 'Control de stock, movimientos y ajustes por almacén.',
            'version' => '1.0.0',
            'dependencies' => [],
            'provider' => \App\Modules\Inventory\Providers\InventoryServiceProvider::class,
        ],
        'accounting' => [
            'name' => 'Contabilidad',
            'description' => 'Asientos contables, libro diario y reportes financieros.',
            'version' => '0.1.0',
            'dependencies' => ['sales'],
            'provider' => \App\Modules\Accounting\Providers\AccountingServiceProvider::class,
        ],
        'ptv-pos' => [
            'name' => 'PTV-POS',
            'description' => 'Punto de venta local con control de caja y turnos.',
            'version' => '0.1.0',
            'dependencies' => ['sales', 'inventory'],
            'provider' => \Stockia\PTVPos\Providers\PTVPosServiceProvider::class,
        ],
    ];

    private function modulesTableReady(): bool
    {
        return Schema::hasTable('modules');
    }

    private function catalog(): array
    {
        return self::CATALOG;
    }

    public function index()
    {
        if (! $this->modulesTableReady()) {
            return view('settings.modules.index', [
                'modulesTableReady' => false,
                'catalog' => collect($this->catalog()),
                'installedModules' => collect(),
            ]);
        }

        $installedModules = Module::orderBy('name')->get()->keyBy('key');
        $catalog = $this->catalog();

        foreach ($installedModules as $key => $module) {
            if (! isset($catalog[$key])) {
                $catalog[$key] = [
                    'name' => $module->name ?: $module->key,
                    'description' => $module->description,
                    'version' => $module->version ?: '0.1.0',
                    'dependencies' => [],
                    'provider' => $module->provider,
                ];
            }
        }

        $catalog = collect($catalog)
            ->map(function ($info) {
                $provider = $info['provider'] ?? null;
                $info['provider_exists'] = $provider ? class_exists($provider) : false;
                return $info;
            })
            ->all();

        return view('settings.modules.index', [
            'modulesTableReady' => true,
            'catalog' => collect($catalog),
            'installedModules' => $installedModules,
        ]);
    }

    public function wizard(Request $request)
    {
        if (! $this->modulesTableReady()) {
            return redirect()->route('settings.modules.index')
                ->withErrors('Primero ejecuta migraciones para habilitar el instalador de módulos.');
        }

        $catalog = $this->catalog();
        $validated = $request->validate([
            'module' => ['required', Rule::in(array_keys($catalog))],
            'step' => ['nullable', 'integer', 'min:1', 'max:3'],
        ]);

        $key = $validated['module'];
        $step = (int) ($validated['step'] ?? 1);
        $moduleInfo = $catalog[$key];
        $installedModules = Module::pluck('enabled', 'key');

        return view('settings.modules.wizard', [
            'key' => $key,
            'step' => $step,
            'moduleInfo' => $moduleInfo,
            'installedModules' => $installedModules,
        ]);
    }

    public function install(Request $request): RedirectResponse
    {
        if (! $this->modulesTableReady()) {
            return back()->withErrors('La tabla modules no existe. Ejecuta php artisan migrate.');
        }

        $catalog = $this->catalog();
        $validated = $request->validate([
            'module' => ['required', Rule::in(array_keys($catalog))],
        ]);

        $key = $validated['module'];
        $moduleInfo = $catalog[$key];

        foreach ($moduleInfo['dependencies'] as $dependencyKey) {
            $dependency = Module::where('key', $dependencyKey)->first();
            if (! $dependency || ! $dependency->enabled) {
                return back()->withErrors("Dependencia pendiente: {$dependencyKey}. Instálala primero.");
            }
        }

        Module::updateOrCreate(
            ['key' => $key],
            [
                'name' => $moduleInfo['name'],
                'description' => $moduleInfo['description'],
                'version' => $moduleInfo['version'],
                'enabled' => true,
                'installed_at' => now(),
            ]
        );

        return redirect()->route('settings.modules.index')
            ->with('success', "Módulo {$moduleInfo['name']} instalado y habilitado.");
    }

    public function toggle(Request $request, Module $module): RedirectResponse
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $module->update([
            'enabled' => (bool) $request->boolean('enabled'),
        ]);

        return redirect()->route('settings.modules.index')
            ->with('success', "Módulo {$module->name} actualizado.");
    }

    public function upload(Request $request, PluginInstaller $installer): RedirectResponse
    {
        if (! $this->modulesTableReady()) {
            return back()->withErrors('La tabla modules no existe. Ejecuta php artisan migrate.');
        }

        $validated = $request->validate([
            'plugin_zip' => ['required', 'file', 'mimes:zip', 'max:51200'],
            'plugin_folder' => ['nullable', 'string', 'max:100'],
            'plugin_force' => ['nullable', 'boolean'],
        ]);

        $path = $request->file('plugin_zip')->store('plugins/uploads');
        $zipPath = Storage::path($path);

        try {
            $result = $installer->installFromZip(
                $zipPath,
                $validated['plugin_folder'] ?? null,
                (bool) ($validated['plugin_force'] ?? false)
            );
        } catch (\Throwable $e) {
            return back()->withErrors($e->getMessage());
        }

        Module::updateOrCreate(
            ['key' => $result['module_key']],
            [
                'name' => $result['module_key'],
                'description' => $result['description'],
                'version' => $result['version'],
                'provider' => $result['provider'],
                'enabled' => false,
            ]
        );

        return redirect()->route('settings.modules.index')->with([
            'success' => 'Plugin cargado. Ejecuta los comandos para completar la instalación.',
            'plugin_install' => [
                'package' => $result['package'],
                'path' => $result['path'],
                'module_key' => $result['module_key'],
                'provider' => $result['provider'],
                'commands' => [
                    'composer update ' . $result['package'],
                    'php artisan migrate',
                ],
            ],
        ]);
    }
}
