<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryMovementController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');
        $currentWarehouseId = (int) session('current_warehouse_id');
        $role = currentRole();
        $canFilterByWarehouse = in_array($role, ['Admin', 'SuperAdmin'], true);

        $selectedWarehouseId = $currentWarehouseId;

    if ($canFilterByWarehouse && $request->filled('warehouse_id')) {
        $validated = $request->validate([
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'type' => ['nullable', 'in:in,out'],
            'reference_type' => ['nullable', 'string', 'max:50'],
        ]);

        $selectedWarehouseId = (int) $validated['warehouse_id'];
    } else {
        $validated = $request->validate([
            'type' => ['nullable', 'in:in,out'],
            'reference_type' => ['nullable', 'string', 'max:50'],
        ]);
    }

        $movements = InventoryMovement::with(['product', 'warehouse', 'user'])
            ->where('company_id', $companyId)
            ->where('warehouse_id', $selectedWarehouseId)
            ->when(!empty($validated['type'] ?? null), function ($query) use ($validated) {
                $query->where('type', $validated['type']);
            })
            ->when(!empty($validated['reference_type'] ?? null), function ($query) use ($validated) {
                $query->where('reference_type', $validated['reference_type']);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $warehouses = $canFilterByWarehouse
            ? Warehouse::where('company_id', $companyId)->orderBy('name')->get()
            : collect();

        return view('inventory_movements.index', compact(
            'movements',
            'warehouses',
            'canFilterByWarehouse',
            'selectedWarehouseId',
            'currentWarehouseId',
            'validated'
        ));
    }


}
