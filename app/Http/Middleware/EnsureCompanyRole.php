<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $company = currentCompany();
        $user = $request->user();

        if (! $user || ! $company) {
            abort(403);
        }

        $role = currentRole();

        if (! in_array($role, $roles)) {
            abort(403);
        }

        return $next($request);
    }
}
