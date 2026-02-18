<?php

namespace App\Http\Controllers;

use App\Models\CompanyUser;
use App\Models\Currency;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CoreAdminController extends Controller
{
    public function index(): View
    {
        $company = currentCompany();
        $companyId = (int) session('current_company_id');

        $warehouses = Warehouse::where('company_id', $companyId)
            ->latest()
            ->get();

        $currencies = Currency::orderBy('code')->get();

        return view('core.admin.index', compact('company', 'warehouses', 'currencies'));
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $company = currentCompany();

        if (! $company) {
            return redirect()->route('company.select')
                ->with('error', 'No hay empresa seleccionada.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:20'],
            'nrc' => ['nullable', 'string', 'max:20'],
            'nombre_razon_social' => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'cod_actividad' => ['nullable', 'string', 'max:10'],
            'desc_actividad' => ['nullable', 'string', 'max:255'],
            'tipo_establecimiento' => ['nullable', 'string', 'max:2'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:120'],
            'departamento' => ['nullable', 'string', 'max:2'],
            'municipio' => ['nullable', 'string', 'max:4'],
            'direccion_complemento' => ['nullable', 'string', 'max:255'],
            'fiscal_address' => ['nullable', 'string', 'max:255'],
            'fiscal_email' => ['nullable', 'email', 'max:255'],
            'fiscal_phone' => ['nullable', 'string', 'max:50'],
            'fiscal_regime' => ['nullable', 'string', 'max:255'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'ticket_footer' => ['nullable', 'string', 'max:1000'],
            'system_name' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'estado' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        DB::transaction(function () use ($request, $validated, $company) {
            if ($request->hasFile('logo')) {
                if ($company->logo_path) {
                    Storage::disk('public')->delete($company->logo_path);
                }

                $validated['logo_path'] = $request->file('logo')->store('companies/logos', 'public');
            }

            $validated['nombre_razon_social'] = $validated['nombre_razon_social'] ?? ($validated['legal_name'] ?? $company->legal_name);
            $validated['nombre_comercial'] = $validated['nombre_comercial'] ?? ($validated['name'] ?? $company->name);
            $validated['tax_id'] = $validated['tax_id'] ?? ($validated['nit'] ?? $company->tax_id);
            $validated['nit'] = $validated['nit'] ?? ($validated['tax_id'] ?? $company->nit);
            $validated['fiscal_address'] = $validated['fiscal_address'] ?? ($validated['direccion_complemento'] ?? $company->fiscal_address);
            $validated['fiscal_email'] = $validated['fiscal_email'] ?? ($validated['correo'] ?? $company->fiscal_email);
            $validated['fiscal_phone'] = $validated['fiscal_phone'] ?? ($validated['telefono'] ?? $company->fiscal_phone);
            $validated['estado'] = $validated['estado'] ?? ($company->estado ?: 'ACTIVO');

            $company->update($validated);
        });

        return redirect()->route('core.admin.index')
            ->with('success', 'Datos de administración actualizados.');
    }

    public function storeWarehouse(Request $request): RedirectResponse
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:warehouses,code'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $companyId) {
            $warehouse = Warehouse::create([
                'company_id' => $companyId,
                'name' => $validated['name'],
                'code' => $validated['code'],
                'location' => $validated['location'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $companyUsers = CompanyUser::where('company_id', $companyId)->get();

            foreach ($companyUsers as $companyUser) {
                $companyUser->warehouses()->syncWithoutDetaching([$warehouse->id]);
            }
        });

        return redirect()->route('core.admin.index')
            ->with('success', 'Almacén creado correctamente.');
    }
}
