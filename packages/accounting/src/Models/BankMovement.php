<?php

namespace Stockia\Accounting\Models;

use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BankMovement extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'user_id',
        'fecha',
        'tipo',
        'monto',
        'numero_cheque',
        'descripcion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (self $movement) {
            if ($movement->tipo === 'deposito') {
                $movement->bankAccount()->increment('saldo_actual', $movement->monto);
            } else {
                $movement->bankAccount()->decrement('saldo_actual', $movement->monto);
            }
        });

        static::deleted(function (self $movement) {
            if ($movement->tipo === 'deposito') {
                $movement->bankAccount()->decrement('saldo_actual', $movement->monto);
            } else {
                $movement->bankAccount()->increment('saldo_actual', $movement->monto);
            }
        });
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
