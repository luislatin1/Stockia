<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyPurchaseItem extends Model
{
    protected $fillable = [
        'legacy_purchase_id',
        'product_id',
        'legacy_id',
        'cantidad',
        'costo_unitario',
        'subtotal',
        'exento',
    ];

    protected $casts = [
        'exento' => 'boolean',
    ];

    public function purchase()
    {
        return $this->belongsTo(LegacyPurchase::class, 'legacy_purchase_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
