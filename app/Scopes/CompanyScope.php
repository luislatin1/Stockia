<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($company = currentCompany()) {
            $builder->where(
                $model->getTable() . '.company_id',
                $company->id
            );
        }
    }
}
