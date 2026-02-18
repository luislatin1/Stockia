<?php

namespace App\Http\Middleware;

use App\Models\CompanyUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $companyId = (int) session('current_company_id');
        $user = $request->user();

        if (! $user || ! $companyId) {
            return redirect()->route('company.select')
                ->with('error', 'Debes seleccionar una empresa para continuar.');
        }

        $role = CompanyUser::where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->value('role');

        if (! in_array($role, $roles)) {
            return redirect()->route('dashboard')
                ->with('error', 'No tienes permisos para acceder a este modulo.');
        }

        return $next($request);
    }
}
