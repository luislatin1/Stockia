<?php

namespace App\Actions\Sales;

use App\Exceptions\SaleValidationException;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Module;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Dte\DteCalculationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Stockia\DteSvMh\Services\DteEmissionService;

/**
 * Secuencia completa de creación de una venta: valida reglas de negocio,
 * calcula impuestos (DteCalculationService), persiste Sale+SaleItem+
 * InventoryMovement en una transacción, y emite el DTE si el módulo
 * dte-sv-mh está activo. Extraída de SaleController::store() para que la
 * web y la API ejecuten exactamente la misma lógica en vez de duplicarla.
 */
class CreateSale
{
    /**
     * @param array{
     *   company_id:int, warehouse_id:int, user_id:int,
     *   products: array<int,int>, document_type: string,
     *   customer_id?: int|null, tipo_dte?: string|null,
     *   cash_received?: float|null, es_credito?: bool,
     *   dias_credito?: int|null,
     * } $params
     */
    public function handle(array $params): Sale
    {
        $companyId = (int) $params['company_id'];
        $warehouseId = (int) $params['warehouse_id'];
        $userId = (int) $params['user_id'];
        $customerId = $params['customer_id'] ?? null;
        $documentType = (string) $params['document_type'];
        $tipoDte = $params['tipo_dte'] ?? null;
        $esCredito = (bool) ($params['es_credito'] ?? false);
        $diasCredito = $esCredito ? (int) ($params['dias_credito'] ?? 0) : null;
        $cashReceived = round((float) ($params['cash_received'] ?? 0), 2);
        $productsPayload = $params['products'] ?? [];

        if ($customerId) {
            $customer = Customer::where('id', (int) $customerId)
                ->where('company_id', $companyId)
                ->first();
            if (! $customer) {
                throw new SaleValidationException('Cliente inválido para la empresa actual.');
            }
        }

        if ($tipoDte === '11') {
            if (! $customerId) {
                throw new SaleValidationException('Para exportación (11) debes seleccionar el cliente del extranjero.');
            }
            if (! $customer->cod_pais || ! $customer->nombre_pais || ! $customer->tipo_persona
                || trim((string) $customer->desc_actividad) === '' || trim((string) $customer->direccion) === '') {
                throw new SaleValidationException('Para exportación el cliente necesita país, tipo de persona, actividad económica y dirección (DTE > Clientes).');
            }
        }

        if ($tipoDte === '03' && ! $customerId) {
            throw new SaleValidationException('Para CCF (03) debes seleccionar un cliente contribuyente.');
        }

        $hasItems = collect($productsPayload)->contains(fn ($qty) => (int) $qty > 0);
        if (! $hasItems) {
            throw new SaleValidationException('Debes agregar al menos un producto con cantidad mayor a cero.');
        }

        $requestedProductIds = collect($productsPayload)
            ->filter(fn ($qty) => (int) $qty > 0)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values();

        $availableCount = Product::where('company_id', $companyId)
            ->whereIn('id', $requestedProductIds)
            ->whereHas('warehouses', function ($query) use ($warehouseId) {
                $query->where('warehouses.id', $warehouseId);
            })
            ->count();

        if ($availableCount !== $requestedProductIds->count()) {
            throw new SaleValidationException('Uno o más productos no pertenecen a la empresa o al almacén actual.');
        }

        $products = Product::where('company_id', $companyId)
            ->whereIn('id', $requestedProductIds)
            ->get()
            ->keyBy('id');

        $lineItems = $this->buildLineItems($productsPayload, $products);

        $calc = app(DteCalculationService::class)->calculate($lineItems, [
            'aplica_retencion_iva' => false,
            'retencion_renta' => 0,
            'exportacion' => $tipoDte === '11',
        ]);
        $totals = $calc['totals'];
        $normalizedItems = $calc['items'];

        $grandTotal = (float) $totals['total'];

        if (! $esCredito && $cashReceived < $grandTotal) {
            throw new SaleValidationException('El efectivo recibido no cubre el total de la venta.');
        }

        if ($esCredito && $cashReceived > $grandTotal) {
            throw new SaleValidationException('El anticipo no puede ser mayor al total de la venta.');
        }

        $changeAmount = $esCredito ? 0.0 : round($cashReceived - $grandTotal, 2);
        $saldoPendiente = $esCredito ? round($grandTotal - $cashReceived, 2) : 0.0;
        $fechaVencimiento = $esCredito ? now()->addDays($diasCredito) : null;

        $saleId = DB::transaction(function () use (
            $products,
            $normalizedItems,
            $warehouseId,
            $companyId,
            $userId,
            $totals,
            $cashReceived,
            $changeAmount,
            $customerId,
            $documentType,
            $tipoDte,
            $esCredito,
            $saldoPendiente,
            $diasCredito,
            $fechaVencimiento
        ) {
            $sale = Sale::create([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'status' => 'completed',
                'tipo_dte' => $tipoDte ?: $this->resolveTipoDte($documentType),
                'numero_interno' => null,
                'gravadas' => $totals['gravadas'],
                'exentas' => $totals['exentas'],
                'no_sujetas' => $totals['no_sujetas'],
                'iva' => $totals['iva'],
                'retencion_iva' => $totals['retencion_iva'],
                'retencion_renta' => $totals['retencion_renta'],
                'descuento_total' => $totals['descuento_total'],
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['iva'],
                'total' => $totals['total'],
                'payment_method' => $esCredito ? 'credit' : 'cash',
                'cash_received' => $cashReceived,
                'change_amount' => $changeAmount,
                'document_type' => $documentType,
                'dias_credito' => $diasCredito,
                'fecha_vencimiento' => $fechaVencimiento,
                'saldo_pendiente' => $saldoPendiente,
            ]);

            $sale->update([
                'numero_interno' => sprintf('V-%06d', (int) $sale->id),
            ]);

            foreach ($normalizedItems as $lineItem) {
                $product = $products->get((int) $lineItem['product_id']);
                if (! $product) {
                    throw new \RuntimeException('Producto inválido en detalle de venta.');
                }

                $saleItem = new SaleItem([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $lineItem['quantity'],
                    'price' => $lineItem['price'],
                    'precio_unitario' => $lineItem['precio_unitario'],
                    'descuento' => $lineItem['descuento'],
                    'monto_gravado' => $lineItem['monto_gravado'],
                    'monto_exento' => $lineItem['monto_exento'],
                    'monto_no_sujeto' => $lineItem['monto_no_sujeto'],
                    'iva_item' => $lineItem['iva_item'],
                    'total_item' => $lineItem['total_item'],
                    'tipo_item' => $lineItem['tipo_item'],
                    'uni_medida' => $lineItem['uni_medida'],
                    'subtotal' => $lineItem['subtotal'],
                ]);
                $saleItem->setRelation('product', $product);
                $saleItem->setRelation('sale', $sale);
                $saleItem->save();

                InventoryMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $lineItem['quantity'],
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'user_id' => $userId,
                ]);
            }

            return (int) $sale->id;
        });

        $sale = Sale::findOrFail($saleId);

        if (Module::isEnabled('dte-sv-mh')) {
            try {
                app(DteEmissionService::class)->emitForSale($sale);
            } catch (\Throwable $e) {
                // La venta ya quedó guardada; el llamador decide cómo avisar
                // de este warning (flash en web, campo "warning" en la API).
                $sale->dte_warning = 'Venta guardada, pero DTE no emitido: '.$e->getMessage();
            }
        }

        return $sale;
    }

    private function buildLineItems(array $productsPayload, Collection $products): array
    {
        $lineItems = [];

        foreach ($productsPayload as $productId => $quantity) {
            $quantity = (int) $quantity;
            $productId = (int) $productId;

            if ($quantity <= 0) {
                continue;
            }

            $product = $products->get($productId);
            if (! $product) {
                throw new SaleValidationException('Uno o más productos no fueron encontrados.');
            }

            $lineItems[] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => (float) $product->price,
                'discount' => 0,
                'afecto_iva' => (bool) ($product->afecto_iva ?? true),
                'category' => (bool) ($product->afecto_iva ?? true) ? 'gravada' : 'exenta',
                'tipo_item' => (int) ($product->tipo_item ?? 1),
                'uni_medida' => isset($product->uni_medida) ? (int) $product->uni_medida : null,
                'product_id' => (int) $product->id,
            ];
        }

        return $lineItems;
    }

    private function resolveTipoDte(string $documentType): ?string
    {
        return $documentType === 'factura' ? '01' : null;
    }
}
