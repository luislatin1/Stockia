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

        if (! $companyId || ! $userId) {
            return redirect()->route('company.select');
        }

        $companyUser = CompanyUser::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->first();

        if (! $companyUser) {
            session()->forget(['current_company_id', 'current_warehouse_id']);
            return redirect()->route('company.select');
        }

        if (! $warehouseId) {
            return redirect()->route('warehouse.select');
        }

        $hasWarehouse = $companyUser->warehouses()
            ->where('warehouses.id', $warehouseId)
            ->exists();

        if (! $hasWarehouse) {
            session()->forget('current_warehouse_id');
            return redirect()->route('warehouse.select');
        }

        return $next($request);
    }
}
