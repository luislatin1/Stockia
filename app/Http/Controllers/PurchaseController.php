<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\LegacyPurchase;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PurchaseController extends Controller
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

    public function index(Request $request)
    {
        [$companyId] = $this->currentContext();

        $view = $request->query('view') === 'historico' ? 'historico' : 'live';

        if ($view === 'historico') {
            $purchases = LegacyPurchase::with('provider')
                ->where('company_id', $companyId)
                ->latest('fecha')
                ->latest('id')
                ->paginate(20);
        } else {
            $purchases = Purchase::with('provider')
                ->where('company_id', $companyId)
                ->latest('fecha')
                ->latest('id')
                ->paginate(20);
        }

        return view('purchases.index', compact('purchases', 'view'));
    }

    public function create()
    {
        [$companyId] = $this->currentContext();

        $providers = Provider::where('company_id', $companyId)->orderBy('nombre')->get();
        $products = Product::where('company_id', $companyId)->orderBy('name')->get(['id', 'name', 'sku', 'barcode']);

        return view('purchases.create', compact('providers', 'products'));
    }

    public function store(Request $request)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $validated = $request->validate([
            'provider_id' => [
                'nullable',
                Rule::exists('providers', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'numero_doc' => 'nullable|string|max:30',
            'fecha' => 'required|date',
            'notes' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.costo_unitario' => 'required|numeric|min:0',
        ]);

        $subtotal = collect($validated['items'])
            ->sum(fn ($item) => $item['cantidad'] * $item['costo_unitario']);

        $purchaseId = DB::transaction(function () use ($validated, $companyId, $warehouseId, $subtotal) {
            $purchase = Purchase::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'provider_id' => $validated['provider_id'] ?? null,
                'user_id' => auth()->id(),
                'numero_doc' => $validated['numero_doc'] ?? null,
                'fecha' => $validated['fecha'],
                'subtotal' => $subtotal,
                'iva' => 0,
                'total' => $subtotal,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $lineSubtotal = $item['cantidad'] * $item['costo_unitario'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'cantidad' => $item['cantidad'],
                    'costo_unitario' => $item['costo_unitario'],
                    'subtotal' => $lineSubtotal,
                ]);

                InventoryMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $item['product_id'],
                    'type' => 'in',
                    'quantity' => $item['cantidad'],
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'user_id' => auth()->id(),
                ]);

                DB::table('product_warehouse')
                    ->where('company_id', $companyId)
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $warehouseId)
                    ->update(['cost' => $item['costo_unitario'], 'updated_at' => now()]);
            }

            return $purchase->id;
        });

        return redirect()->route('purchases.show', $purchaseId)
            ->with('success', 'Compra registrada correctamente.');
    }

    public function show(Purchase $purchase)
    {
        [$companyId] = $this->currentContext();

        if ((int) $purchase->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $purchase->load(['items.product', 'provider']);

        return view('purchases.show', compact('purchase'));
    }
}
