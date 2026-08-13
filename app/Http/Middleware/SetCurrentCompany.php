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
            $selectedCompany = null;

            if ($currentCompanyId) {
                $selectedCompany = $user->companies()
                    ->where('companies.id', (int) $currentCompanyId)
                    ->first();

                if (! $selectedCompany) {
                    session()->forget(['current_company_id', 'current_warehouse_id']);
                }
            }

            if (! $selectedCompany) {
                $selectedCompany = $user->companies()
                    ->orderBy('companies.id')
                    ->first();

                if ($selectedCompany) {
                    session(['current_company_id' => (int) $selectedCompany->id]);
                }
            }

            if ($selectedCompany) {
                app()->instance('currentCompany', $selectedCompany);

                if ($selectedCompany->timezone) {
                    config(['app.timezone' => $selectedCompany->timezone]);
                    date_default_timezone_set($selectedCompany->timezone);
                }
            }
        }

        return $next($request);
    }
}
