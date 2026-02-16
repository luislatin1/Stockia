<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetCurrentCompany
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = $request->user();
            $currentCompanyId = session('current_company_id');

            if ($currentCompanyId) {
                $hasAccess = $user->companies()
                    ->where('companies.id', (int) $currentCompanyId)
                    ->exists();

                if (! $hasAccess) {
                    session()->forget(['current_company_id', 'current_warehouse_id']);
                    $currentCompanyId = null;
                }
            }

            if (! $currentCompanyId) {
                $firstCompanyId = $user->companies()
                    ->orderBy('companies.id')
                    ->value('companies.id');

                if ($firstCompanyId) {
                    session(['current_company_id' => (int) $firstCompanyId]);
                }
            }

            $selectedCompany = null;
            if (session()->has('current_company_id')) {
                $selectedCompany = $user->companies()
                    ->where('companies.id', (int) session('current_company_id'))
                    ->first();
            }

            if ($selectedCompany) {
                app()->instance('currentCompany', $selectedCompany);
            }
        }

        return $next($request);
    }
}
