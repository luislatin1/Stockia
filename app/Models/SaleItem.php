<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
protected $fillable = [
    'sale_id',
    'product_id',
    'quantity',
    'price',
    'precio_unitario',
    'descuento',
    'monto_gravado',
    'monto_exento',
    'monto_no_sujeto',
    'iva_item',
    'total_item',
    'tipo_item',
    'uni_medida',
    'subtotal'
];

protected $casts = [
    'price' => 'decimal:2',
    'precio_unitario' => 'decimal:2',
    'descuento' => 'decimal:2',
    'monto_gravado' => 'decimal:2',
    'monto_exento' => 'decimal:2',
    'monto_no_sujeto' => 'decimal:2',
    'iva_item' => 'decimal:2',
    'total_item' => 'decimal:2',
    'subtotal' => 'decimal:2',
];

protected static function booted(): void
{
    static::created(function (self $item) {
        if (! Module::isEnabled('sales')) {
            return;
        }

        $garantiaMeses = $item->product?->garantia_meses;

        if (! $garantiaMeses) {
            return;
        }

        $sale = $item->sale;
        $fechaInicio = $sale->created_at->toDateString();

        Warranty::create([
            'company_id' => $sale->company_id,
            'sale_id' => $sale->id,
            'sale_item_id' => $item->id,
            'product_id' => $item->product_id,
            'customer_id' => $sale->customer_id,
            'fecha_inicio' => $fechaInicio,
            'fecha_vencimiento' => \Illuminate\Support\Carbon::parse($fechaInicio)->addMonths((int) $garantiaMeses),
        ]);
    });
}

public function product()
{
    return $this->belongsTo(Product::class);
}

public function sale()
{
    return $this->belongsTo(Sale::class);
}

public function warranty()
{
    return $this->hasOne(Warranty::class);
}
}
