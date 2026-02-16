<?php

namespace App\Http\Controllers;

use App\Models\CompanyUser;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index()
    {
        $companyId = (int) session('current_company_id');

        $warehouses = Warehouse::where('company_id', $companyId)
            ->latest()
            ->get();

        return view('warehouses.index', compact('warehouses'));
    }

    public function store(Request $request): RedirectResponse
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

        return redirect()->route('warehouses.index')
            ->with('success', 'Almacén creado correctamente.');
    }
}
