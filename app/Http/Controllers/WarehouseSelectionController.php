<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanyUser;

class WarehouseSelectionController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');

        $companyUser = CompanyUser::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->first();

        $warehouses = $companyUser->warehouses;

        return view('auth.select-warehouse', compact('warehouses'));
    }

    public function store(Request $request)
    {
        session([
            'current_warehouse_id' => $request->warehouse_id
        ]);

        return redirect()->route('dashboard');
    }
}
