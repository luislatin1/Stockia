<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::with('currency')
            ->withCount('warehouses')
            ->latest()
            ->get();

        $currencies = Currency::orderBy('code')->get();

        return view('companies.index', compact('companies', 'currencies'));
    }

    public function store(Request $request): RedirectResponse
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
        });

        return redirect()->route('companies.index')
            ->with('success', 'Compañía creada correctamente.');
    }
}

