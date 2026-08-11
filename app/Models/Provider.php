<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'legacy_id',
        'nombre',
        'nit',
        'nrc',
        'dui',
        'telefono',
        'email',
        'direccion',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
