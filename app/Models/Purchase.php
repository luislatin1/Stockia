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
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
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
