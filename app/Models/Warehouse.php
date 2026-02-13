<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('stock', 'min_stock', 'cost')
            ->withTimestamps();
    }
}
