<?php

namespace App\Http\Middleware;

use App\Models\CompanyUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $userId = (int) optional($request->user())->id;

        if ($request->isMethod('get')) {
            session(['url.intended' => $request->fullUrl()]);
        }

        if (! $companyId || ! $userId) {
            if ($userId) {
                $hasCompany = \App\Models\CompanyUser::where('user_id', $userId)->exists();
                if (! $hasCompany) {
                    return redirect()->route('setup.step1');
                }
            }
            return redirect()->route('company.select')
                ->with('error', 'Debes seleccionar una empresa para continuar.');
        }

        $companyUser = CompanyUser::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->first();

        if (! $companyUser) {
            session()->forget(['current_company_id', 'current_warehouse_id']);
            return redirect()->route('company.select')
                ->with('error', 'Tu usuario no tiene acceso a la empresa seleccionada.');
        }

        if (! $warehouseId) {
            return redirect()->route('warehouse.select')
                ->with('error', 'Debes seleccionar un almacen para continuar.');
        }

        $hasWarehouse = $companyUser->warehouses()
            ->where('warehouses.id', $warehouseId)
            ->exists();

        if (! $hasWarehouse) {
            session()->forget('current_warehouse_id');
            return redirect()->route('warehouse.select')
                ->with('error', 'El almacen seleccionado no esta asignado a tu usuario.');
        }

        return $next($request);
    }
}
