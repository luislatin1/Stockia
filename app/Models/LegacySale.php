<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class LegacySale extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'legacy_id',
        'fecha',
        'hora',
        'tipo',
        'tipo_documento',
        'numero_doc',
        'cliente_nombre',
        'subtotal',
        'iva',
        'descuento',
        'total',
        'anulada',
    ];

    protected $casts = [
        'anulada' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(LegacySaleItem::class);
    }
}
