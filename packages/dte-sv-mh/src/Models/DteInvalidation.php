<?php

namespace Stockia\DteSvMh\Models;

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
        'json_evento',
    ];

    protected $casts = [
        'fecha_invalidacion' => 'datetime',
        'respuesta_hacienda' => 'array',
        'json_evento' => 'array',
    ];

    public function dte()
    {
        return $this->belongsTo(Dte::class, 'dte_id');
    }
}
