<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WarehouseSelectionController extends Controller
{
    public function index()
    {
        $companyId = (int) session('current_company_id');

        $companyUser = CompanyUser::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->with('warehouses')
            ->first();

        if (! $companyUser) {
            session()->forget(['current_company_id', 'current_warehouse_id']);
            return redirect()->route('company.select');
        }

        $warehouses = $companyUser->warehouses;

        if ($warehouses->isEmpty()) {
            $companyWarehouseIds = Warehouse::where('company_id', $companyId)
                ->pluck('id')
                ->all();

            if (! empty($companyWarehouseIds)) {
                $companyUser->warehouses()->syncWithoutDetaching($companyWarehouseIds);
                $companyUser->load('warehouses');
                $warehouses = $companyUser->warehouses;
            }
        }

        if ($warehouses->count() === 1) {
            session([
                'current_warehouse_id' => $warehouses->first()->id,
            ]);

            return redirect()->route('dashboard');
        }

        return view('auth.select-warehouse', compact('warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer'],
        ]);

        $companyId = (int) session('current_company_id');
        $warehouseId = (int) $validated['warehouse_id'];

        $companyUser = CompanyUser::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $companyUser) {
            session()->forget(['current_company_id', 'current_warehouse_id']);
            return redirect()->route('company.select');
        }

        $hasWarehouse = $companyUser->warehouses()
            ->where('warehouses.id', $warehouseId)
            ->exists();

        if (! $hasWarehouse) {
            return back()->withErrors('No tienes acceso a ese almacén.');
        }

        session([
            'current_warehouse_id' => $warehouseId
        ]);

        return redirect()->route('dashboard');
    }
}
