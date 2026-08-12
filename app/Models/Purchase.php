<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'provider_id',
        'user_id',
        'numero_doc',
        'fecha',
        'subtotal',
        'iva',
        'total',
        'notes',
        'es_credito',
        'dias_credito',
        'fecha_vencimiento',
        'saldo_pendiente',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_vencimiento' => 'date',
        'es_credito' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function getEstaVencidaAttribute(): bool
    {
        return $this->es_credito
            && $this->saldo_pendiente > 0
            && $this->fecha_vencimiento !== null
            && $this->fecha_vencimiento->isPast();
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
