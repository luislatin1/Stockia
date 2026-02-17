<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Currency;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SetupWizardController extends Controller
{
    public function step1()
    {
        $currencies = Currency::orderBy('code')->get();
        return view('setup.step1', compact('currencies'));
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'timezone' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $company = Company::create($validated);
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

