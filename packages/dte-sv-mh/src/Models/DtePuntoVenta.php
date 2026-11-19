<?php

namespace Stockia\DteSvMh\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Punto de venta de un establecimiento. `codigo` es la segunda mitad de la 3.ª
 * sección del número de control: "P" + 3 dígitos.
 */
class DtePuntoVenta extends Model
{
    public const CODIGO_REGEX = '/^P[0-9]{3}$/';

    protected $table = 'dte_puntos_venta';

    protected $fillable = [
        'establecimiento_id',
        'codigo',
        'nombre',
        'codigo_confirmado',
        'activo',
    ];

    protected $casts = [
        'codigo_confirmado' => 'boolean',
        'activo' => 'boolean',
    ];

    public function establecimiento()
    {
        return $this->belongsTo(DteEstablecimiento::class, 'establecimiento_id');
    }
}
