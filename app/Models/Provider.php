<?php

namespace App\Models;

use App\Support\FiscalId;
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
        'departamento',
        'municipio',
        'distrito',
        'dias_credito_default',
    ];

    /** Guarda NIT/NRC/DUI con guiones cuando son válidos; los valores dudosos no se tocan. */
    protected static function booted(): void
    {
        static::saving(function (self $p) {
            $p->nit = FiscalId::normalizarNit($p->nit);
            $p->nrc = FiscalId::normalizarNrc($p->nrc);
            $p->dui = FiscalId::normalizarDui($p->dui);
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
