<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'legacy_id',
        'nombre',
        'descripcion',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
