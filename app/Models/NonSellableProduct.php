<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonSellableProduct extends Model
{
    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',
        'quantity',
        'condition',
        'source_type',
        'source_id',
        'reason',
        'reported_by_user_id',
    ];
}
