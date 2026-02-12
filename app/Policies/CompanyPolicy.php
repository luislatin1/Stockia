<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function update(User $user, Company $company): bool
    {
        return $user->companies()
            ->where('company_id', $company->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function view(User $user, Company $company): bool
    {
        return $user->companies()
            ->where('company_id', $company->id)
            ->exists();
    }
}
