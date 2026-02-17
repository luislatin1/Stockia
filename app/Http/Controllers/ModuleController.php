<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ],
        'inventory' => [
            'name' => 'Inventario',
            'description' => 'Control de stock, movimientos y ajustes por almacén.',
            'version' => '1.0.0',
            'dependencies' => [],
        ],
        'accounting' => [
            'name' => 'Contabilidad',
            'description' => 'Asientos contables, libro diario y reportes financieros.',
            'version' => '0.1.0',
            'dependencies' => ['sales'],
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

        return view('settings.modules.index', [
            'modulesTableReady' => true,
            'catalog' => collect($this->catalog()),
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
}

