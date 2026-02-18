<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
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

    private function ensureProductContext(Product $product, int $companyId, int $warehouseId): void
    {
        if ((int) $product->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $existsInWarehouse = DB::table('product_warehouse')
            ->where('company_id', $companyId)
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->exists();

        if (! $existsInWarehouse) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    public function index(Request $request)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $search = trim((string) $request->query('q', ''));

        $query = Product::with(['warehouses' => function ($query) use ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }])
        ->where('company_id', $companyId);

        if ($search !== '') {
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('barcode', $search)
                    ->orWhere('sku', $search);
            });
        }

        if ($request->low_stock) {
            $query->whereHas('warehouses', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->whereColumn('product_warehouse.stock', '<=', 'product_warehouse.min_stock');
            });
        }

        $products = $query->get();

        return view('products.index', compact('products', 'search'));
    }

    public function create()
    {
        $this->currentContext();

        return view('products.create');
    }

    public function store(Request $request)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        Warehouse::where('company_id', $companyId)->findOrFail($warehouseId);

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:255',
            'min_stock' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $companyId, $warehouseId) {
            $initialStock = (int) ($request->stock ?? 0);

            $product = Product::create([
                'company_id' => $companyId,
                'name' => $request->name,
                'sku' => $request->filled('sku') ? trim((string) $request->sku) : null,
                'barcode' => $request->filled('barcode') ? trim((string) $request->barcode) : null,
                'price' => $request->price,
                'description' => $request->description,
            ]);

            DB::table('product_warehouse')->insert([
                'company_id' => $companyId,
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'stock' => 0,
                'min_stock' => $request->min_stock ?? 0,
                'cost' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($initialStock > 0) {
                InventoryMovement::create([
                    'company_id' => $companyId,
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'type' => 'in',
                    'quantity' => $initialStock,
                    'reference_type' => 'initial',
                    'reference_id' => null,
                    'user_id' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureProductContext($product, $companyId, $warehouseId);

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureProductContext($product, $companyId, $warehouseId);

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($product->id),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($product->id),
            ],
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'min_stock' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $product, $warehouseId, $companyId) {
            $product->update([
                'name' => $request->name,
                'sku' => $request->filled('sku') ? trim((string) $request->sku) : null,
                'barcode' => $request->filled('barcode') ? trim((string) $request->barcode) : null,
                'price' => $request->price,
                'description' => $request->description,
            ]);

            DB::table('product_warehouse')
                ->where('company_id', $companyId)
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->update([
                    'min_stock' => $request->min_stock ?? 0,
                ]);
        });

        return redirect()->route('products.index')
            ->with('success', 'Producto actualizado.');
    }

    public function adjust(Product $product)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureProductContext($product, $companyId, $warehouseId);

        return view('products.adjust', compact('product'));
    }

    public function processAdjustment(Request $request, Product $product)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureProductContext($product, $companyId, $warehouseId);

        $request->validate([
            'quantity' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:255',
        ]);

        $quantity = (int) $request->quantity;

        $pivot = DB::table('product_warehouse')
            ->where('company_id', $companyId)
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $pivot) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if (($pivot->stock + $quantity) < 0) {
            return back()->withErrors('El ajuste dejaría el stock en negativo.');
        }

        DB::transaction(function () use ($product, $quantity, $request, $warehouseId, $companyId) {
            InventoryMovement::create([
                'company_id' => $companyId,
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'type' => $quantity > 0 ? 'in' : 'out',
                'quantity' => abs($quantity),
                'reference_type' => 'adjustment',
                'reference_id' => null,
                'reason' => $request->reason,
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Stock ajustado correctamente.');
    }

    public function show(Product $product)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        if ((int) $product->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $movements = $product->movements()
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->latest()
            ->paginate(10);

        return view('products.show', compact('product', 'movements'));
    }

    public function destroy(Product $product)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureProductContext($product, $companyId, $warehouseId);

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Producto eliminado.');
    }
}
