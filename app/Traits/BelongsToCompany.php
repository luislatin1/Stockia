<?php

namespace App\Traits;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (Model $model) {
            if (! $model->company_id && currentCompany()) {
                $model->company_id = currentCompany()->id;
            }
        });
    }
}
