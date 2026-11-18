<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    private function currentContext(): array
    {
        return [(int) session('current_company_id'), (int) session('current_warehouse_id')];
    }

    public function index(Request $request): JsonResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $search = trim((string) $request->query('q', ''));

        $query = Product::where('company_id', $companyId);

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', '%'.$search.'%')
                    ->orWhere('codigo', $search)
                    ->orWhere('barcode', $search)
                    ->orWhere('sku', $search);
            });
        }

        $products = $query->orderBy('name')->paginate((int) $request->query('per_page', 30));

        return ProductResource::collection($products)->response();
    }

    public function show(Product $product): JsonResponse
    {
        [$companyId] = $this->currentContext();

        if ((int) $product->company_id !== $companyId) {
            abort(404);
        }

        return (new ProductResource($product))->response();
    }

    public function store(Request $request): JsonResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('products', 'name')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'sku' => [
                'nullable', 'string', 'max:100',
                Rule::unique('products', 'sku')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'codigo' => [
                'nullable', 'string', 'max:50',
                Rule::unique('products', 'codigo')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'barcode' => [
                'nullable', 'string', 'max:255',
                Rule::unique('products', 'barcode')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'afecto_iva' => ['nullable', 'boolean'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'provider_id' => ['nullable', Rule::exists('providers', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
        ]);

        $product = DB::transaction(function () use ($validated, $companyId, $warehouseId) {
            $initialStock = (int) ($validated['stock'] ?? 0);

            $product = Product::create([
                'company_id' => $companyId,
                'name' => trim($validated['name']),
                'codigo' => $validated['codigo'] ?? null,
                'sku' => $validated['sku'] ?? null,
                'barcode' => $validated['barcode'] ?? null,
                'afecto_iva' => $validated['afecto_iva'] ?? true,
                'price' => $validated['price'],
                'description' => $validated['description'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'provider_id' => $validated['provider_id'] ?? null,
            ]);

            DB::table('product_warehouse')->insert([
                'company_id' => $companyId,
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'stock' => 0,
                'min_stock' => $validated['min_stock'] ?? 0,
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

            return $product;
        });

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();

        if ((int) $product->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('products', 'name')->where(fn ($q) => $q->where('company_id', $companyId))->ignore($product->id),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'afecto_iva' => ['nullable', 'boolean'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'provider_id' => ['nullable', Rule::exists('providers', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
        ]);

        DB::transaction(function () use ($validated, $product, $companyId, $warehouseId) {
            $product->update([
                'name' => trim($validated['name']),
                'price' => $validated['price'],
                'description' => $validated['description'] ?? null,
                'afecto_iva' => $validated['afecto_iva'] ?? $product->afecto_iva,
                'category_id' => $validated['category_id'] ?? null,
                'provider_id' => $validated['provider_id'] ?? null,
            ]);

            if (array_key_exists('min_stock', $validated)) {
                DB::table('product_warehouse')
                    ->where('company_id', $companyId)
                    ->where('product_id', $product->id)
                    ->where('warehouse_id', $warehouseId)
                    ->update(['min_stock' => $validated['min_stock']]);
            }
        });

        return (new ProductResource($product->fresh()))->response();
    }
}
