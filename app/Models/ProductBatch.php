<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'codigo_lote',
        'fecha_vencimiento',
        'cantidad',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
    ];

    public function getEstadoVencimientoAttribute(): string
    {
        if ($this->fecha_vencimiento->isPast()) {
            return 'vencido';
        }

        if (now()->diffInDays($this->fecha_vencimiento) <= 30) {
            return 'por_vencer';
        }

        return 'vigente';
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
