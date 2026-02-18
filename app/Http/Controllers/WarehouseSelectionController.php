<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class WarehouseSelectionController extends Controller
{
    private function openPosSessionForCurrentCompany(int $companyId): ?object
    {
        if (! $companyId || ! Schema::hasTable('pos_sessions')) {
            return null;
        }

        return DB::table('pos_sessions')
            ->where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->orderByDesc('id')
            ->first();
    }

    public function index()
    {
        $companyId = (int) session('current_company_id');
        $openSession = $this->openPosSessionForCurrentCompany($companyId);

        if ($openSession) {
            if (Route::has('ptvpos.close')) {
                return redirect()->route('ptvpos.close')
                    ->with('error', 'Tienes una caja abierta. Debes cerrarla antes de cambiar de almacen.');
            }

            return redirect()->route('dashboard')
                ->with('error', 'Tienes una caja abierta. Debes cerrarla antes de cambiar de almacen.');
        }

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

            return redirect()->intended(route('dashboard'));
        }

        return view('auth.select-warehouse', compact('warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer'],
        ]);

        $companyId = (int) session('current_company_id');
        $openSession = $this->openPosSessionForCurrentCompany($companyId);

        if ($openSession) {
            if (Route::has('ptvpos.close')) {
                return redirect()->route('ptvpos.close')
                    ->with('error', 'No puedes cambiar de almacen con caja abierta. Cierra caja primero.');
            }

            return redirect()->route('dashboard')
                ->with('error', 'No puedes cambiar de almacen con caja abierta. Cierra caja primero.');
        }

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

        return redirect()->intended(route('dashboard'));
    }
}
