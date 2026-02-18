<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dte extends Model
{
    protected $table = 'dtes';

    protected $fillable = [
        'company_id',
        'sale_id',
        'tipo_dte',
        'codigo_generacion',
        'numero_control',
        'json_original',
        'json_firmado',
        'sello_recepcion',
        'respuesta_hacienda',
        'estado',
        'fecha_envio',
    ];

    protected $casts = [
        'json_original' => 'array',
        'json_firmado' => 'array',
        'respuesta_hacienda' => 'array',
        'fecha_envio' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
