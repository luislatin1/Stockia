<?php

namespace App\Models;
use App\Models\Product;
use App\Models\Company;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('stock', 'min_stock', 'cost')
            ->withTimestamps();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
