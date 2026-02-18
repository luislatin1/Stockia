<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DteInvalidation extends Model
{
    protected $table = 'dte_invalidaciones';

    protected $fillable = [
        'dte_id',
        'tipo_invalidacion',
        'motivo',
        'fecha_invalidacion',
        'estado_envio',
        'respuesta_hacienda',
    ];

    protected $casts = [
        'fecha_invalidacion' => 'datetime',
        'respuesta_hacienda' => 'array',
    ];

    public function dte()
    {
        return $this->belongsTo(Dte::class, 'dte_id');
    }
}
