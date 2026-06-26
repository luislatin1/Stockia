<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Currency;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupWizardController extends Controller
{
    public function step1()
    {
        $currencies = Currency::orderBy('code')->get();

        if ($currencies->isEmpty()) {
            Currency::firstOrCreate(
                ['code' => 'USD'],
                ['name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2]
            );
            Currency::firstOrCreate(
                ['code' => 'CRC'],
                ['name' => 'Colón', 'symbol' => '₡', 'decimals' => 2]
            );
            $currencies = Currency::orderBy('code')->get();
        }

        $actividades = Schema::hasTable('dte_cat_actividades_economicas')
            ? DB::table('dte_cat_actividades_economicas')->where('activo', true)->orderBy('codigo')->get()
            : collect();

        $tiposEstablecimiento = Schema::hasTable('dte_cat_tipo_establecimiento')
            ? DB::table('dte_cat_tipo_establecimiento')->where('activo', true)->orderBy('codigo')->get()
            : collect();

        $departamentos = Schema::hasTable('dte_departamentos')
            ? DB::table('dte_departamentos')->where('activo', true)->orderBy('nombre')->get()
            : collect();

        $municipios = Schema::hasTable('dte_municipios')
            ? DB::table('dte_municipios')->where('activo', true)->orderBy('nombre')->get()
            : collect();

        return view('setup.step1', compact('currencies', 'actividades', 'tiposEstablecimiento', 'departamentos', 'municipios'));
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:20'],
            'nrc' => ['nullable', 'string', 'max:20'],
            'cod_actividad' => ['nullable', 'string', 'max:6', 'exists:dte_cat_actividades_economicas,codigo'],
            'tipo_establecimiento' => ['nullable', 'string', 'max:2', 'exists:dte_cat_tipo_establecimiento,codigo'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:120'],
            'departamento' => ['nullable', 'string', 'size:2', 'exists:dte_departamentos,codigo'],
            'municipio' => ['nullable', 'string', 'size:4', 'exists:dte_municipios,codigo'],
            'direccion_complemento' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'timezone' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $descActividad = null;
            if (! empty($validated['cod_actividad'])) {
                $actividad = DB::table('dte_cat_actividades_economicas')
                    ->where('codigo', $validated['cod_actividad'])
                    ->first();
                $descActividad = $actividad?->descripcion;
            }

            $company = Company::create([
                'name' => $validated['name'],
                'legal_name' => $validated['legal_name'] ?? null,
                'tax_id' => $validated['tax_id'] ?? ($validated['nit'] ?? null),
                'nit' => $validated['nit'] ?? ($validated['tax_id'] ?? null),
                'nrc' => $validated['nrc'] ?? null,
                'nombre_razon_social' => $validated['legal_name'] ?? $validated['name'],
                'nombre_comercial' => $validated['name'],
                'cod_actividad' => $validated['cod_actividad'] ?? null,
                'desc_actividad' => $descActividad,
                'tipo_establecimiento' => $validated['tipo_establecimiento'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'correo' => $validated['correo'] ?? null,
                'departamento' => $validated['departamento'] ?? null,
                'municipio' => $validated['municipio'] ?? null,
                'direccion_complemento' => $validated['direccion_complemento'] ?? null,
                'fiscal_address' => $validated['direccion_complemento'] ?? null,
                'fiscal_email' => $validated['correo'] ?? null,
                'fiscal_phone' => $validated['telefono'] ?? null,
                'estado' => 'ACTIVO',
                'currency_id' => $validated['currency_id'],
                'timezone' => $validated['timezone'],
            ]);
            auth()->user()->companies()->syncWithoutDetaching([
                $company->id => ['role' => 'SuperAdmin'],
            ]);
            session(['current_company_id' => $company->id]);
        });

        return redirect()->route('setup.step2');
    }

    public function step2()
    {
        return view('setup.step2');
    }

    public function storeStep2(Request $request): RedirectResponse
    {
        $companyId = (int) session('current_company_id');
        if (! $companyId) {
            return redirect()->route('setup.step1')->withErrors('Primero crea la empresa.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:warehouses,code'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $companyId) {
            $warehouse = Warehouse::create([
                'company_id' => $companyId,
                'name' => $validated['name'],
                'code' => $validated['code'],
                'location' => $validated['location'] ?? null,
                'is_active' => true,
            ]);

            $companyUser = CompanyUser::where('company_id', $companyId)
                ->where('user_id', auth()->id())
                ->first();

            if ($companyUser) {
                $companyUser->warehouses()->syncWithoutDetaching([$warehouse->id]);
            }

            session(['current_warehouse_id' => $warehouse->id]);
        });

        return redirect()->route('setup.done');
    }

    public function done()
    {
        return view('setup.done');
    }
}
