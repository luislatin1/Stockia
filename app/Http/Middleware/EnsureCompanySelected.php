<?php

namespace App\Http\Middleware;

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
        if (!session()->has('current_company_id')) {
            return redirect()->route('company.select');
        }

        if (!session()->has('current_warehouse_id')) {
            return redirect()->route('warehouse.select');
        }

        return $next($request);
    }
}
