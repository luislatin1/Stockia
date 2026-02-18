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
            'fiscal_address' => ['nullable', 'string', 'max:255'],
            'fiscal_email' => ['nullable', 'email', 'max:255'],
            'fiscal_phone' => ['nullable', 'string', 'max:50'],
            'fiscal_regime' => ['nullable', 'string', 'max:255'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'ticket_footer' => ['nullable', 'string', 'max:1000'],
            'system_name' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        DB::transaction(function () use ($request, $validated, $company) {
            if ($request->hasFile('logo')) {
                if ($company->logo_path) {
                    Storage::disk('public')->delete($company->logo_path);
                }

                $validated['logo_path'] = $request->file('logo')->store('companies/logos', 'public');
            }

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
