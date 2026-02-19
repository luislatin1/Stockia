<?php

namespace App\Http\Controllers;

use App\Models\CompanyUser;
use App\Models\Currency;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        [$departments, $municipalities, $establishmentTypes] = $this->catalogs();

        return view('core.admin.index', compact(
            'company',
            'warehouses',
            'currencies',
            'departments',
            'municipalities',
            'establishmentTypes'
        ));
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $company = currentCompany();

        if (! $company) {
            return redirect()->route('company.select')
                ->with('error', 'No hay empresa seleccionada.');
        }

        $departmentCodes = $this->availableDepartmentCodes();
        $establishmentTypeCodes = $this->availableEstablishmentTypeCodes();

        $departmentRules = ['nullable', 'string', 'size:2'];
        if (! empty($departmentCodes)) {
            $departmentRules[] = Rule::in($departmentCodes);
        }

        $establishmentRules = ['nullable', 'string', 'size:2'];
        if (! empty($establishmentTypeCodes)) {
            $establishmentRules[] = Rule::in($establishmentTypeCodes);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:20'],
            'nrc' => ['nullable', 'string', 'max:20'],
            'nombre_razon_social' => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'cod_actividad' => ['nullable', 'string', 'max:10'],
            'desc_actividad' => ['nullable', 'string', 'max:255'],
            'tipo_establecimiento' => $establishmentRules,
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:120'],
            'departamento' => $departmentRules,
            'municipio' => ['nullable', 'string', 'size:2'],
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

        $validator->after(function ($validator) use ($request) {
            $department = (string) $request->input('departamento', '');
            $municipality = (string) $request->input('municipio', '');

            if (($department === '' && $municipality !== '') || ($department !== '' && $municipality === '')) {
                $validator->errors()->add('municipio', 'Debes seleccionar departamento y municipio juntos.');
                return;
            }

            if ($department !== '' && $municipality !== '' && ! $this->isValidMunicipalityForDepartment($department, $municipality)) {
                $validator->errors()->add('municipio', 'El municipio seleccionado no corresponde al departamento.');
            }
        });

        $validated = $validator->validate();

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

    private function catalogs(): array
    {
        $departments = collect();
        $municipalities = collect();
        $establishmentTypes = collect();

        if (Schema::hasTable('dte_departamentos')) {
            $departments = DB::table('dte_departamentos')
                ->where('activo', true)
                ->orderBy('codigo')
                ->get(['codigo', 'nombre']);
        }

        if (Schema::hasTable('dte_municipios')) {
            $municipalities = DB::table('dte_municipios')
                ->where('activo', true)
                ->orderBy('codigo')
                ->get(['codigo', 'departamento_codigo', 'nombre'])
                ->map(function ($row) {
                    $row->codigo_local = substr((string) $row->codigo, -2);
                    return $row;
                });
        }

        if (Schema::hasTable('dte_cat_tipo_establecimiento')) {
            $establishmentTypes = DB::table('dte_cat_tipo_establecimiento')
                ->where('activo', true)
                ->orderBy('codigo')
                ->get(['codigo', 'descripcion']);
        }

        return [$departments, $municipalities, $establishmentTypes];
    }

    private function availableDepartmentCodes(): array
    {
        if (! Schema::hasTable('dte_departamentos')) {
            return [];
        }

        return DB::table('dte_departamentos')
            ->where('activo', true)
            ->pluck('codigo')
            ->map(fn ($code) => (string) $code)
            ->values()
            ->all();
    }

    private function availableEstablishmentTypeCodes(): array
    {
        if (! Schema::hasTable('dte_cat_tipo_establecimiento')) {
            return [];
        }

        return DB::table('dte_cat_tipo_establecimiento')
            ->where('activo', true)
            ->pluck('codigo')
            ->map(fn ($code) => (string) $code)
            ->values()
            ->all();
    }

    private function isValidMunicipalityForDepartment(string $departmentCode, string $municipalityCode): bool
    {
        if (! Schema::hasTable('dte_municipios')) {
            return true;
        }

        $fullCode = $departmentCode . $municipalityCode;

        return DB::table('dte_municipios')
            ->where('departamento_codigo', $departmentCode)
            ->where('codigo', $fullCode)
            ->exists();
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
