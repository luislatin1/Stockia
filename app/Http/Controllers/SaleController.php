<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SaleController extends Controller
{
    private const IVA_RATE = 0.13;

    private function currentContext(): array
    {
        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');

        if (! $companyId || ! $warehouseId) {
            abort(Response::HTTP_BAD_REQUEST, 'Empresa o almacén no seleccionado.');
        }

        return [$companyId, $warehouseId];
    }

    private function findSaleInContext(int $saleId, int $companyId, int $warehouseId): Sale
    {
        return Sale::where('id', $saleId)
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->firstOrFail();
    }

    public function index()
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $sales = Sale::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->with('user')
            ->latest()
            ->get();

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $products = Product::where('company_id', $companyId)
            ->whereHas('warehouses', function ($query) use ($warehouseId) {
                $query->where('warehouses.id', $warehouseId);
            })
            ->with(['warehouses' => function ($query) use ($warehouseId) {
                $query->where('warehouses.id', $warehouseId);
            }])
            ->get();

        return view('sales.create', compact('products'));
    }

    public function show($id)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $sale = $this->findSaleInContext((int) $id, $companyId, $warehouseId);
        $sale->load(['items.product', 'company', 'warehouse', 'user']);

        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        if ((int) $sale->company_id !== $companyId || (int) $sale->warehouse_id !== $warehouseId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($sale->status !== 'pending') {
            return redirect()->route('sales.index')
                ->withErrors('No se puede editar una venta completada o cancelada.');
        }

        return view('sales.edit', compact('sale'));
    }

    public function store(Request $request)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $validated = $request->validate([
            'products' => ['required', 'array'],
            'products.*' => ['nullable', 'integer', 'min:0'],
            'cash_received' => ['required', 'numeric', 'min:0'],
            'document_type' => ['required', 'in:ticket,factura'],
        ]);

        $productsPayload = $validated['products'] ?? [];
        $hasItems = collect($productsPayload)->contains(fn ($qty) => (int) $qty > 0);

        if (! $hasItems) {
            return back()->withErrors('Debes agregar al menos un producto con cantidad mayor a cero.');
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
            return back()->withErrors('Uno o más productos no pertenecen a la empresa o al almacén actual.');
        }

        $products = Product::where('company_id', $companyId)
            ->whereIn('id', $requestedProductIds)
            ->get()
            ->keyBy('id');

        $lineItems = [];
        $subtotal = 0.0;

        foreach ($productsPayload as $productId => $quantity) {
            $quantity = (int) $quantity;
            $productId = (int) $productId;

            if ($quantity <= 0) {
                continue;
            }

            $product = $products->get($productId);

            if (! $product) {
                return back()->withErrors('Uno o más productos no fueron encontrados.');
            }

            $lineSubtotal = round(((float) $product->price) * $quantity, 2);
            $subtotal += $lineSubtotal;

            $lineItems[] = [
                'product' => $product,
                'quantity' => $quantity,
                'price' => (float) $product->price,
                'subtotal' => $lineSubtotal,
            ];
        }

        $taxTotal = round($subtotal * self::IVA_RATE, 2);
        $grandTotal = round($subtotal + $taxTotal, 2);
        $cashReceived = round((float) $validated['cash_received'], 2);

        if ($cashReceived < $grandTotal) {
            return back()->withErrors('El efectivo recibido no cubre el total de la venta.');
        }

        $changeAmount = round($cashReceived - $grandTotal, 2);

        DB::transaction(function () use (
            $lineItems,
            $warehouseId,
            $companyId,
            $subtotal,
            $taxTotal,
            $grandTotal,
            $cashReceived,
            $changeAmount,
            $validated
        ) {
            $sale = Sale::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'user_id' => auth()->id(),
                'status' => 'completed',
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $grandTotal,
                'payment_method' => 'cash',
                'cash_received' => $cashReceived,
                'change_amount' => $changeAmount,
                'document_type' => $validated['document_type'],
            ]);

            foreach ($lineItems as $lineItem) {
                $product = $lineItem['product'];
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $lineItem['quantity'],
                    'price' => $lineItem['price'],
                    'subtotal' => $lineItem['subtotal'],
                ]);

                InventoryMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $lineItem['quantity'],
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'user_id' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('sales.index')
            ->with('success', 'Venta registrada correctamente');
    }

    public function ticket(Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        if ((int) $sale->company_id !== $companyId || (int) $sale->warehouse_id !== $warehouseId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $sale->load(['items.product', 'company', 'warehouse', 'user']);

        return view('sales.ticket', compact('sale'));
    }

    public function invoice(Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        if ((int) $sale->company_id !== $companyId || (int) $sale->warehouse_id !== $warehouseId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $sale->load(['items.product', 'company', 'warehouse', 'user']);

        return view('sales.invoice', compact('sale'));
    }

    public function cancel(Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        if ((int) $sale->company_id !== $companyId || (int) $sale->warehouse_id !== $warehouseId) {
            abort(Response::HTTP_FORBIDDEN);
        }

        try {
            $sale->cancel();
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }

        return redirect()
            ->route('sales.index')
            ->with('success', 'Venta cancelada correctamente');
    }
}
