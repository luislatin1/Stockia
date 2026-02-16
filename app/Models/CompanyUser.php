<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyUser extends Model
{
    protected $table = 'company_user';

    protected $fillable = [
        'company_id',
        'user_id',
        'role'
    ];

    public function warehouses()
    {
        return $this->belongsToMany(
            Warehouse::class,
            'company_user_warehouse'
        );
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

