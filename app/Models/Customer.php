<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\FiscalId;
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
        'cod_actividad',
        'desc_actividad',
        'cod_pais',
        'nombre_pais',
        'tipo_persona',
        'direccion',
        'telefono',
        'correo',
        'es_contribuyente',
        'dias_credito_default',
    ];

    protected $casts = [
        'es_contribuyente' => 'boolean',
    ];

    /** Número de documento y NRC en formato con guiones cuando son válidos (evita duplicados 0123-4 / 01234). */
    protected static function booted(): void
    {
        static::saving(function (self $c) {
            $c->numero_documento = FiscalId::normalizarDocumento($c->tipo_documento, $c->numero_documento);
            $c->nrc = FiscalId::normalizarNrc($c->nrc);
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
