<?php

namespace Stockia\Inventory\Models;

use App\Models\Product;
use App\Models\Warehouse;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ProductLocation extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'estante',
        'cantidad',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
