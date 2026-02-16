<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryMovement;
use App\Models\SaleItem;
use App\Models\Warehouse;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'warehouse_id',
        'status',
        'subtotal',
        'tax_total',
        'total',
        'payment_method',
        'cash_received',
        'change_amount',
        'document_type',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'cash_received' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class, 'reference_id')
            ->where('reference_type', 'sale');
    }

    public function isEditable()
    {
        return $this->status === 'pending';
    }

    public function isCancellable()
    {
        return $this->status === 'completed';
    }

    public function cancel(): void
    {
        if ($this->status !== 'completed') {
            throw new \Exception("Solo ventas completadas pueden cancelarse.");
        }

        DB::transaction(function () {

            foreach ($this->items as $item) {
                InventoryMovement::create([
                    'company_id'   => $this->company_id,
                    'product_id'   => $item->product_id,
                    'warehouse_id' => $this->warehouse_id,
                    'type'         => 'in',
                    'quantity'     => $item->quantity,
                    'reference_type' => 'sale_cancelled',
                    'reference_id'   => $this->id,
                    'user_id'      => auth()->id(),
                ]);
            }

            $this->update([
                'status' => 'cancelled'
            ]);
        });
    }
}
