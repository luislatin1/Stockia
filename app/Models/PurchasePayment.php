<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'purchase_id',
        'user_id',
        'fecha',
        'monto',
        'forma_pago',
        'referencia',
        'notes',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (self $payment) {
            $payment->purchase()->decrement('saldo_pendiente', $payment->monto);
        });

        static::deleted(function (self $payment) {
            $payment->purchase()->increment('saldo_pendiente', $payment->monto);
        });
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
