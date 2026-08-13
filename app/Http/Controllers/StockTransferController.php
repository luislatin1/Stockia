<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StockTransferController extends Controller
{
    private function currentContext(): array
    {
        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');

        if (! $companyId || ! $warehouseId) {
            abort(Response::HTTP_BAD_REQUEST, 'Empresa o almacén no seleccionado.');
        }

        return [$companyId, $warehouseId];
    }

    public function index()
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $transfers = StockTransfer::where('company_id', $companyId)
            ->where(function ($query) use ($warehouseId) {
                $query->where('origin_warehouse_id', $warehouseId)
                    ->orWhere('destination_warehouse_id', $warehouseId);
            })
            ->with(['originWarehouse', 'destinationWarehouse'])
            ->latest('id')
            ->paginate(25);

        return view('stock-transfers.index', compact('transfers'));
    }

    public function create()
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $destinations = Warehouse::where('company_id', $companyId)
            ->where('id', '!=', $warehouseId)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->whereHas('warehouses', fn ($q) => $q->where('warehouses.id', $warehouseId))
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'barcode']);

        return view('stock-transfers.create', compact('destinations', 'products'));
    }

    public function store(Request $request)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $validated = $request->validate([
            'destination_warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'fecha' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $destinationWarehouseId = (int) $validated['destination_warehouse_id'];

        if ($destinationWarehouseId === $warehouseId) {
            return back()->withErrors('La bodega destino debe ser distinta a la bodega actual.')->withInput();
        }

        foreach ($validated['items'] as $item) {
            $productId = (int) $item['product_id'];

            $origin = DB::table('product_warehouse')
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if (! $origin || $origin->stock < $item['quantity']) {
                $product = Product::find($productId);
                return back()->withErrors('Stock insuficiente para "'.($product->name ?? $productId).'" en la bodega actual.')->withInput();
            }

            $destinationExists = DB::table('product_warehouse')
                ->where('product_id', $productId)
                ->where('warehouse_id', $destinationWarehouseId)
                ->exists();

            if (! $destinationExists) {
                $product = Product::find($productId);
                return back()->withErrors('El producto "'.($product->name ?? $productId).'" no está asignado a la bodega destino. Agrégalo primero desde Productos.')->withInput();
            }
        }

        $transferId = DB::transaction(function () use ($validated, $companyId, $warehouseId, $destinationWarehouseId) {
            $transfer = StockTransfer::create([
                'company_id' => $companyId,
                'origin_warehouse_id' => $warehouseId,
                'destination_warehouse_id' => $destinationWarehouseId,
                'user_id' => auth()->id(),
                'fecha' => $validated['fecha'],
                'status' => 'enviado',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);

                InventoryMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $item['product_id'],
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $transfer->id,
                    'user_id' => auth()->id(),
                ]);
            }

            return $transfer->id;
        });

        return redirect()->route('stock-transfers.show', $transferId)
            ->with('success', 'Traslado enviado correctamente.');
    }

    public function show(StockTransfer $stockTransfer)
    {
        [$companyId] = $this->currentContext();

        if ((int) $stockTransfer->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $stockTransfer->load(['items.product', 'originWarehouse', 'destinationWarehouse', 'user', 'receivedBy']);

        return view('stock-transfers.show', compact('stockTransfer'));
    }

    public function receive(StockTransfer $stockTransfer)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        if ((int) $stockTransfer->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($stockTransfer->status !== 'enviado') {
            abort(Response::HTTP_BAD_REQUEST, 'Este traslado ya fue procesado.');
        }

        if ((int) $stockTransfer->destination_warehouse_id !== $warehouseId) {
            return back()->withErrors('Debes tener seleccionada la bodega destino para confirmar la recepción.');
        }

        DB::transaction(function () use ($stockTransfer, $companyId) {
            foreach ($stockTransfer->items as $item) {
                InventoryMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $stockTransfer->destination_warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'reference_type' => 'stock_transfer',
                    'reference_id' => $stockTransfer->id,
                    'user_id' => auth()->id(),
                ]);
            }

            $stockTransfer->update([
                'status' => 'recibido',
                'received_by_user_id' => auth()->id(),
                'received_at' => now(),
            ]);
        });

        return redirect()->route('stock-transfers.show', $stockTransfer)
            ->with('success', 'Recepción confirmada, stock actualizado en la bodega destino.');
    }

    public function cancel(StockTransfer $stockTransfer)
    {
        [$companyId] = $this->currentContext();

        if ((int) $stockTransfer->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($stockTransfer->status !== 'enviado') {
            abort(Response::HTTP_BAD_REQUEST, 'Solo un traslado en tránsito puede anularse.');
        }

        DB::transaction(function () use ($stockTransfer, $companyId) {
            foreach ($stockTransfer->items as $item) {
                InventoryMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $stockTransfer->origin_warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'reference_type' => 'stock_transfer_cancelled',
                    'reference_id' => $stockTransfer->id,
                    'user_id' => auth()->id(),
                ]);
            }

            $stockTransfer->update(['status' => 'anulado']);
        });

        return redirect()->route('stock-transfers.show', $stockTransfer)
            ->with('success', 'Traslado anulado, el stock regresó a la bodega de origen.');
    }
}
