<?php

namespace App\Http\Middleware;

use App\Models\CompanyUser;
use Closure;
use Illuminate\Http\Request;

/**
 * Equivalente a SetCurrentCompany pero para la API: en vez de recordar la
 * última empresa elegida en sesión, exige que cada request declare
 * explícitamente con qué empresa/bodega quiere trabajar vía headers. Deja
 * el resultado en session() igual que el flujo web para que todo el código
 * existente que lee current_company_id/current_warehouse_id (CompanyScope,
 * currentCompany(), Product, los Gate::define de Permission, etc.) siga
 * funcionando sin cambios.
 */
class ResolveApiCompanyContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $companyId = (int) $request->header('X-Company-Id');

        if (! $companyId) {
            return response()->json([
                'message' => 'Falta el header X-Company-Id.',
            ], 422);
        }

        $company = $user->companies()
            ->where('companies.id', $companyId)
            ->first();

        if (! $company) {
            return response()->json([
                'message' => 'Tu usuario no tiene acceso a la empresa indicada en X-Company-Id.',
            ], 422);
        }

        session(['current_company_id' => $company->id]);
        app()->instance('currentCompany', $company);

        if ($company->timezone) {
            config(['app.timezone' => $company->timezone]);
            date_default_timezone_set($company->timezone);
        }

        $warehouseId = (int) $request->header('X-Warehouse-Id');

        if ($warehouseId) {
            $companyUser = CompanyUser::where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->with(['warehouses' => fn ($q) => $q->where('warehouses.id', $warehouseId)])
                ->first();

            if (! $companyUser || $companyUser->warehouses->isEmpty()) {
                return response()->json([
                    'message' => 'Tu usuario no tiene acceso a la bodega indicada en X-Warehouse-Id.',
                ], 422);
            }

            session(['current_warehouse_id' => $warehouseId]);
        } else {
            session()->forget('current_warehouse_id');
        }

        return $next($request);
    }
}
