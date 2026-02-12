<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'type',
        'quantity',
        'reference',
    ];

public function product()
{
    return $this->belongsTo(Product::class);
}
}
