<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Currency;

class Company extends Model
{
    protected $fillable = [
        'name',
        'legal_name',
        'tax_id',
        'nit',
        'nrc',
        'nombre_razon_social',
        'nombre_comercial',
        'cod_actividad',
        'desc_actividad',
        'tipo_establecimiento',
        'telefono',
        'correo',
        'departamento',
        'municipio',
        'direccion_complemento',
        'certificado_firma',
        'certificado_expira_en',
        'estado',
        'fiscal_address',
        'fiscal_email',
        'fiscal_phone',
        'fiscal_regime',
        'invoice_prefix',
        'ticket_footer',
        'logo_path',
        'system_name',
        'timezone',
        'currency_id',
    ];

    protected $casts = [
        'certificado_expira_en' => 'datetime',
    ];

    public function users()
{
    return $this->belongsToMany(User::class)
        ->withPivot('role')
        ->withTimestamps();
}

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
