<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'warehouse_id' => $this->warehouse_id,
            'user_id' => $this->user_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status,
            'document_type' => $this->document_type,
            'tipo_dte' => $this->tipo_dte,
            'numero_interno' => $this->numero_interno,
            'payment_method' => $this->payment_method,
            'subtotal' => (float) $this->subtotal,
            'tax_total' => (float) $this->tax_total,
            'total' => (float) $this->total,
            'cash_received' => (float) $this->cash_received,
            'change_amount' => (float) $this->change_amount,
            'saldo_pendiente' => (float) $this->saldo_pendiente,
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            // Señal no persistida que deja CreateSale si la venta se guardó
            // pero el DTE no se pudo emitir (ver app/Actions/Sales/CreateSale.php).
            'dte_warning' => $this->dte_warning ?? null,
        ];
    }
}
