<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Currency;

class Company extends Model
{
    protected $fillable = [
        'name',
        'legal_name',
        'tax_id',
        'timezone',
        'currency_id',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
