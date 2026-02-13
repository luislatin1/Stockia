<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;
use App\Models\InventoryMovement;
use App\Models\SaleItem;
use App\Models\Sale;
use App\Models\Warehouse;

class Product extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'price',
        'description',
    ];

    /*
    |--------------------------------------------------------------------------
    | Global Scope (Multi-Empresa)
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::addGlobalScope('company', function ($query) {
            if (session()->has('current_company_id')) {
                $query->where('company_id', session('current_company_id'));
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'product_warehouse')
            ->withPivot('stock', 'min_stock', 'cost')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Helpers (por bodega)
    |--------------------------------------------------------------------------
    */

    public function stockInWarehouse($warehouseId): int
    {
        $warehouse = $this->warehouses()
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $warehouse?->pivot->stock ?? 0;
    }

    public function minStockInWarehouse($warehouseId): int
    {
        $warehouse = $this->warehouses()
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $warehouse?->pivot->min_stock ?? 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Business Logic
    |--------------------------------------------------------------------------
    */

    public function removeStock(int $warehouseId, int $quantity): void
    {
        $warehouse = $this->warehouses()
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $warehouse) {
            throw new \Exception("El producto no existe en esta bodega.");
        }

        $currentStock = $warehouse->pivot->stock;

        if ($quantity > $currentStock) {
            throw new \Exception("Stock insuficiente para {$this->name}");
        }

        $this->warehouses()->updateExistingPivot($warehouseId, [
            'stock' => $currentStock - $quantity
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (para Dashboard y reportes)
    |--------------------------------------------------------------------------
    */

    public function scopeLowStock($query, $warehouseId)
    {
        return $query->whereHas('warehouses', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId)
              ->whereColumn('product_warehouse.stock', '<=', 'product_warehouse.min_stock');
        });
    }

    public function scopeOutOfStock($query, $warehouseId)
    {
        return $query->whereHas('warehouses', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId)
              ->where('product_warehouse.stock', 0);
        });
    }

    public function getStockAttribute()
{
    $warehouseId = session('current_warehouse_id');

    if (!$warehouseId) {
        return 0;
    }

    $warehouse = $this->warehouses
        ->where('id', $warehouseId)
        ->first();

    return $warehouse?->pivot->stock ?? 0;
}

    public function getMinStockAttribute()
    {
        $warehouseId = session('current_warehouse_id');

        if (!$warehouseId) {
            return 0;
        }

        $warehouse = $this->warehouses
            ->where('id', $warehouseId)
            ->first();

        return $this->minStockInWarehouse($warehouseId);
    }
    // public function getCurrentStockAttribute()
    // {
    //     $in = $this->movements()->where('type', 'in')->sum('quantity');
    //     $out = $this->movements()->where('type', 'out')->sum('quantity');

    //     return $in - $out;
    // }

    
}

