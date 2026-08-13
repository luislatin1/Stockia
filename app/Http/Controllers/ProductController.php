<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Provider;
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

    private function warehouseStockSubquery(int $warehouseId)
    {
        return DB::table('product_warehouse')
            ->select('product_id', 'stock', 'min_stock')
            ->where('warehouse_id', $warehouseId);
    }

    public function index(Request $request)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $search = trim((string) $request->query('q', ''));

        $availability = (string) $request->query('availability', '');
        if (! in_array($availability, ['disponible', 'low_stock', 'agotado'], true)) {
            $availability = '';
        }

        $sort = (string) $request->query('sort', 'name');
        if (! in_array($sort, ['name', 'price', 'stock'], true)) {
            $sort = 'name';
        }

        $dir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Product::query()
            ->leftJoinSub($this->warehouseStockSubquery($warehouseId), 'pw', 'pw.product_id', '=', 'products.id')
            ->where('products.company_id', $companyId)
            ->select('products.*')
            ->selectRaw('COALESCE(pw.stock, 0) as warehouse_stock')
            ->selectRaw('COALESCE(pw.min_stock, 0) as warehouse_min_stock');

        if ($search !== '') {
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('products.name', 'like', '%' . $search . '%')
                    ->orWhere('products.codigo', $search)
                    ->orWhere('products.barcode', $search)
                    ->orWhere('products.sku', $search);
            });
        }

        if ($availability === 'disponible') {
            $query->whereRaw('COALESCE(pw.stock, 0) > COALESCE(pw.min_stock, 0)');
        } elseif ($availability === 'low_stock') {
            $query->whereRaw('COALESCE(pw.stock, 0) > 0 AND COALESCE(pw.stock, 0) <= COALESCE(pw.min_stock, 0)');
        } elseif ($availability === 'agotado') {
            $query->whereRaw('COALESCE(pw.stock, 0) <= 0');
        }

        if ($sort === 'price') {
            $query->orderBy('products.price', $dir);
        } elseif ($sort === 'stock') {
            $query->orderByRaw('COALESCE(pw.stock, 0) ' . $dir);
        } else {
            $query->orderBy('products.name', $dir);
        }

        $products = $query->paginate(30)->withQueryString();

        $statsRow = DB::table('products as p')
            ->leftJoinSub($this->warehouseStockSubquery($warehouseId), 'pw', 'pw.product_id', '=', 'p.id')
            ->where('p.company_id', $companyId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN COALESCE(pw.stock, 0) > 0 AND COALESCE(pw.stock, 0) <= COALESCE(pw.min_stock, 0) THEN 1 ELSE 0 END) as low_stock')
            ->selectRaw('SUM(CASE WHEN COALESCE(pw.stock, 0) <= 0 THEN 1 ELSE 0 END) as out_of_stock')
            ->selectRaw('SUM(p.price * COALESCE(pw.stock, 0)) as inventory_value')
            ->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'low_stock' => (int) $statsRow->low_stock,
            'out_of_stock' => (int) $statsRow->out_of_stock,
            'inventory_value' => (float) $statsRow->inventory_value,
        ];

        $viewData = compact('products', 'search', 'availability', 'sort', 'dir');

        if ($request->ajax()) {
            return view('products._results', $viewData);
        }

        return view('products.index', $viewData + ['stats' => $stats]);
    }

    public function checkUnique(Request $request): \Illuminate\Http\JsonResponse
    {
        [$companyId] = $this->currentContext();

        $field = (string) $request->query('field', '');
        $value = trim((string) $request->query('value', ''));
        $productId = $request->query('product_id');

        $allowed = ['sku', 'codigo', 'barcode', 'name'];
        if (! in_array($field, $allowed, true) || $value === '') {
            return response()->json(['available' => true]);
        }

        $query = DB::table('products')
            ->where('company_id', $companyId)
            ->where($field, $value);

        if ($productId) {
            $query->where('id', '!=', (int) $productId);
        }

        $exists = $query->exists();

        $labels = ['sku' => 'SKU', 'codigo' => 'Código DTE', 'barcode' => 'Código de barras', 'name' => 'nombre'];

        return response()->json([
            'available' => ! $exists,
            'message'   => $exists ? 'El ' . $labels[$field] . ' ya está registrado en esta empresa.' : '',
        ]);
    }

    public function create()
    {
        [$companyId] = $this->currentContext();

        $categories = Category::where('company_id', $companyId)->orderBy('nombre')->get();
        $providers = Provider::where('company_id', $companyId)->orderBy('nombre')->get();

        return view('products.create', compact('categories', 'providers'));
    }

    public function store(Request $request)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        Warehouse::where('company_id', $companyId)->findOrFail($warehouseId);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'codigo' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'codigo')->where(fn ($query) => $query->where('company_id', $companyId)),
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
            'tipo_item' => 'nullable|integer|in:1,2',
            'uni_medida' => 'nullable|integer|min:1',
            'afecto_iva' => 'nullable|boolean',
            'min_stock' => 'nullable|integer|min:0',
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'provider_id' => [
                'nullable',
                Rule::exists('providers', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
        ]);

        DB::transaction(function () use ($request, $companyId, $warehouseId) {
            $initialStock = (int) ($request->stock ?? 0);

            $product = Product::create([
                'company_id' => $companyId,
                'name' => trim((string) $request->name),
                'codigo' => $request->filled('codigo') ? trim((string) $request->codigo) : null,
                'sku' => $request->filled('sku') ? trim((string) $request->sku) : null,
                'barcode' => $request->filled('barcode') ? trim((string) $request->barcode) : null,
                'tipo_item' => (int) ($request->tipo_item ?? 1),
                'uni_medida' => $request->filled('uni_medida') ? (int) $request->uni_medida : null,
                'afecto_iva' => $request->boolean('afecto_iva', true),
                'price' => $request->price,
                'description' => $request->description,
                'category_id' => $request->filled('category_id') ? (int) $request->category_id : null,
                'provider_id' => $request->filled('provider_id') ? (int) $request->provider_id : null,
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

        $categories = Category::where('company_id', $companyId)->orderBy('nombre')->get();
        $providers = Provider::where('company_id', $companyId)->orderBy('nombre')->get();

        return view('products.edit', compact('product', 'categories', 'providers'));
    }

    public function update(Request $request, Product $product)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureProductContext($product, $companyId, $warehouseId);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($product->id),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($product->id),
            ],
            'codigo' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'codigo')
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
            'tipo_item' => 'nullable|integer|in:1,2',
            'uni_medida' => 'nullable|integer|min:1',
            'afecto_iva' => 'nullable|boolean',
            'min_stock' => 'nullable|integer|min:0',
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'provider_id' => [
                'nullable',
                Rule::exists('providers', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
        ]);

        DB::transaction(function () use ($request, $product, $warehouseId, $companyId) {
            $product->update([
                'name' => trim((string) $request->name),
                'codigo' => $request->filled('codigo') ? trim((string) $request->codigo) : null,
                'sku' => $request->filled('sku') ? trim((string) $request->sku) : null,
                'barcode' => $request->filled('barcode') ? trim((string) $request->barcode) : null,
                'tipo_item' => (int) ($request->tipo_item ?? 1),
                'uni_medida' => $request->filled('uni_medida') ? (int) $request->uni_medida : null,
                'afecto_iva' => $request->boolean('afecto_iva', true),
                'price' => $request->price,
                'description' => $request->description,
                'category_id' => $request->filled('category_id') ? (int) $request->category_id : null,
                'provider_id' => $request->filled('provider_id') ? (int) $request->provider_id : null,
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
