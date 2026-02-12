<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class SetCurrentCompany
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {

            // Si no existe empresa en sesión, asignar la primera
            if (!session()->has('current_company_id')) {

                $company = Company::first();

                if ($company) {
                    session(['current_company_id' => $company->id]);
                }
            }

            // Registrar empresa actual
            if (session()->has('current_company_id')) {

                $company = Company::find(session('current_company_id'));

                if ($company) {
                    app()->instance('currentCompany', $company);
                }
            }
        }

        return $next($request);
    }
}
