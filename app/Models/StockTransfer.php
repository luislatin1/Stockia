<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'origin_warehouse_id',
        'destination_warehouse_id',
        'user_id',
        'fecha',
        'status',
        'received_by_user_id',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'fecha' => 'date',
        'received_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function originWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }
}
