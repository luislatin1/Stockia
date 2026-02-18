<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'company_id',
        'tipo_documento',
        'numero_documento',
        'nrc',
        'nombre',
        'departamento',
        'municipio',
        'direccion',
        'telefono',
        'correo',
        'es_contribuyente',
    ];

    protected $casts = [
        'es_contribuyente' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
