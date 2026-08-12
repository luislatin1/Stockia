<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class LegacyPurchase extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'provider_id',
        'legacy_id',
        'numero_doc',
        'fecha',
        'hora',
        'iva',
        'total',
        'dias_credito',
        'anulada',
    ];

    protected $casts = [
        'anulada' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(LegacyPurchaseItem::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
