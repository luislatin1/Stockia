<?php

namespace Stockia\Sales\Models;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Warranty extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'sale_id',
        'sale_item_id',
        'product_id',
        'customer_id',
        'fecha_inicio',
        'fecha_vencimiento',
        'estado',
        'descripcion_falla',
        'fecha_reclamo',
        'resolucion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_reclamo' => 'date',
    ];

    public function getEstaVencidaAttribute(): bool
    {
        return $this->estado === 'vigente' && $this->fecha_vencimiento->isPast();
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
