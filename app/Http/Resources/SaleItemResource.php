<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'quantity' => (int) $this->quantity,
            'precio_unitario' => (float) $this->precio_unitario,
            'descuento' => (float) $this->descuento,
            'monto_gravado' => (float) $this->monto_gravado,
            'monto_exento' => (float) $this->monto_exento,
            'iva_item' => (float) $this->iva_item,
            'total_item' => (float) $this->total_item,
        ];
    }
}
