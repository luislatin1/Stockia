<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacySaleItem extends Model
{
    protected $fillable = [
        'legacy_sale_id',
        'product_id',
        'legacy_id',
        'descripcion',
        'cantidad',
        'precio_venta',
        'subtotal',
        'exento',
    ];

    protected $casts = [
        'exento' => 'boolean',
    ];

    public function sale()
    {
        return $this->belongsTo(LegacySale::class, 'legacy_sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
