<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'bank_id',
        'currency_id',
        'numero_cuenta',
        'tipo_cuenta',
        'saldo_actual',
    ];

    protected $casts = [
        'saldo_actual' => 'decimal:2',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function movements()
    {
        return $this->hasMany(BankMovement::class);
    }
}
