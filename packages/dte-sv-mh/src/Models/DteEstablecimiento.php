<?php

namespace Stockia\DteSvMh\Models;

use App\Models\Company;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Establecimiento fiscal (Casa Matriz, Sucursal, Bodega o Patio, CAT-009).
 * `codigo` es la primera mitad de la 3.ª sección del número de control:
 * letra [M|B|S|P] + 3 dígitos.
 */
class DteEstablecimiento extends Model
{
    use BelongsToCompany;

    public const CODIGO_REGEX = '/^[MBSP][0-9]{3}$/';

    /** CAT-009 → letra sugerida para el código. */
    public const LETRA_POR_TIPO = ['02' => 'M', '01' => 'S', '04' => 'B', '07' => 'P'];

    protected $table = 'dte_establecimientos';

    protected $fillable = [
        'company_id',
        'codigo',
        'tipo_establecimiento',
        'nombre',
        'departamento',
        'municipio',
        'distrito',
        'direccion',
        'telefono',
        'correo',
        'codigo_confirmado',
        'activo',
    ];

    protected $casts = [
        'codigo_confirmado' => 'boolean',
        'activo' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function puntosVenta()
    {
        return $this->hasMany(DtePuntoVenta::class, 'establecimiento_id');
    }
}
