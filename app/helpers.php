<?php

use App\Models\Company;

if (! function_exists('currentCompany')) {
    function currentCompany(): ?Company
    {
        return session('current_company_id')
            ? Company::find(session('current_company_id'))
            : null;
    }
}

if (! function_exists('currentRole')) {
    function currentRole(): ?string
    {
        $company = currentCompany();
        $user = request()->user();

        if (! $company || ! $user) {
            return null;
        }

        return $user->companies()
            ->where('company_id', $company->id)
            ->first()?->pivot->role;
    }
}
