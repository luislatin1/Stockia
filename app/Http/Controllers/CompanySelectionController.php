<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class CompanySelectionController extends Controller
{
    public function index()
    {
        $companies = auth()->user()->companies;

        if ($companies->count() === 1) {
            session([
                'current_company_id' => $companies->first()->id
            ]);
            session()->forget('current_warehouse_id');

            return redirect()->route('warehouse.select');
        }

        return view('auth.select-company', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        $companyId = (int) $validated['company_id'];
        $hasAccess = auth()->user()->companies()
            ->where('companies.id', $companyId)
            ->exists();

        if (! $hasAccess) {
            return back()->withErrors('No tienes acceso a esa empresa.');
        }

        session([
            'current_company_id' => $companyId
        ]);
        session()->forget('current_warehouse_id');

        return redirect()->route('warehouse.select');
    }
}

