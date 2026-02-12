<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;
use App\Models\InventoryMovement;
use App\Models\SaleItem;
use App\Models\Sale;

class Product extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'price',
        'stock',
        'min_stock',
    ];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }



    // public function getCurrentStockAttribute()
    // {
    //     $in = $this->movements()->where('type', 'in')->sum('quantity');
    //     $out = $this->movements()->where('type', 'out')->sum('quantity');

    //     return $in - $out;
    // }

    
}

