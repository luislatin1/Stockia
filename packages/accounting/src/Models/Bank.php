<?php

namespace Stockia\Accounting\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'nombre',
    ];

    public function accounts()
    {
        return $this->hasMany(BankAccount::class);
    }
}
