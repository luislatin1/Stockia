<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanySelectionController extends Controller
{
    public function index()
    {
        $companies = auth()->user()->companies;

        if ($companies->count() === 1) {
            session([
                'current_company_id' => $companies->first()->id
            ]);

            return redirect()->route('warehouse.select');
        }

        return view('auth.select-company', compact('companies'));
    }

    public function store(Request $request)
    {
        session([
            'current_company_id' => $request->company_id
        ]);

        return redirect()->route('warehouse.select');
    }
}

