<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CompanyUser;


class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $companyId = session('current_company_id');

        $companyUser = CompanyUser::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->first();

        if (! in_array($companyUser->role, $roles)) {
            abort(403);
        }

        return $next($request);
    }
}

