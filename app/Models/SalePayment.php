<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'sale_id',
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
            $payment->sale()->decrement('saldo_pendiente', $payment->monto);
        });

        static::deleted(function (self $payment) {
            $payment->sale()->increment('saldo_pendiente', $payment->monto);
        });
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
