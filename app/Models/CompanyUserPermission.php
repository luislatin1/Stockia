<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyUserPermission extends Model
{
    protected $fillable = [
        'company_user_id',
        'permission_key',
        'allowed',
    ];

    protected $casts = [
        'allowed' => 'boolean',
    ];

    public function companyUser()
    {
        return $this->belongsTo(CompanyUser::class);
    }
}
