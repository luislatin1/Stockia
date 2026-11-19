<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class Customer extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'tipo_documento',
        'numero_documento',
        'nrc',
        'nombre',
        'departamento',
        'municipio',
        'distrito',
        'direccion',
        'telefono',
        'correo',
        'es_contribuyente',
        'dias_credito_default',
    ];

    protected $casts = [
        'es_contribuyente' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
