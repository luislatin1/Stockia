<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class InventoryMovement extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'user_id',
    ];

protected static function booted()
{
    static::created(function ($movement) {

        $pivot = \DB::table('product_warehouse')
            ->where('product_id', $movement->product_id)
            ->where('warehouse_id', $movement->warehouse_id)
            ->first();

        if (! $pivot) {
            throw new \Exception("El producto no existe en esta bodega.");
        }

        if ($movement->type === 'in') {

            \DB::table('product_warehouse')
                ->where('product_id', $movement->product_id)
                ->where('warehouse_id', $movement->warehouse_id)
                ->increment('stock', $movement->quantity);

        } elseif ($movement->type === 'out') {

            if ($movement->quantity > $pivot->stock) {
                throw new \Exception("Stock insuficiente.");
            }

            \DB::table('product_warehouse')
                ->where('product_id', $movement->product_id)
                ->where('warehouse_id', $movement->warehouse_id)
                ->decrement('stock', $movement->quantity);
        }
    });
}

public function product()
{
    return $this->belongsTo(Product::class);
}

public function warehouse()
{
    return $this->belongsTo(Warehouse::class);
}

}