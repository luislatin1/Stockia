<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'codigo' => $this->codigo,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => (float) $this->price,
            'description' => $this->description,
            'afecto_iva' => (bool) $this->afecto_iva,
            'category_id' => $this->category_id,
            'provider_id' => $this->provider_id,
            // $this->stock/min_stock usan el accessor existente del modelo
            // (Product::getStockAttribute), resuelto contra la bodega activa
            // en esta request (session current_warehouse_id).
            'stock' => (int) $this->stock,
            'min_stock' => (int) $this->min_stock,
        ];
    }
}
