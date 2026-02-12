<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
    'company_id',
    'total'
];

public function items()
{
    return $this->hasMany(SaleItem::class);
}
}
