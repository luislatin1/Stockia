<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Warehouse;

session([
    'current_warehouse_id' => 1
]);

class ProductController extends Controller
{

public function index(Request $request)
    {
    $warehouseId = session('current_warehouse_id');

        if (!$warehouseId) {
            abort(400, 'No hay bodega seleccionada.');
        }
    $query = Product::with(['warehouses' => function ($query) use ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }])
        ->where('company_id', session('current_company_id'));

    if ($request->low_stock) {
        $query->whereHas('warehouses', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId)
              ->whereColumn('product_warehouse.stock', '<=', 'product_warehouse.min_stock');
        });
    }

    $products = $query->get();

    return view('products.index', compact('products'));
}

    public function create()
        {
            return view('products.create');
        }

    public function store(Request $request)
    {
            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'stock' => 'nullable|integer|min:0',
                'description' => 'nullable|string|max:255',
            ]);

            $product = Product::create([
                'company_id' => session('current_company_id'),
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description,
            ]);

        $warehouseId = 1; // luego lo hacemos dinámico

        // 2️⃣ Crear registro en product_warehouse
        DB::table('product_warehouse')->insert([
            'company_id' => session('current_company_id'),
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'stock' => 0,
            'min_stock' => $request->min_stock ?? 0,
            'cost' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

     // 3️⃣ Si hay stock inicial → movement + update
        if ($request->stock > 0) {

                InventoryMovement::create([
                    'company_id' => session('current_company_id'),
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'type' => 'in',
                    'quantity' => $request->stock,
                    'reference_type' => 'initial',
                    'reference_id' => null,
                    'user_id' => auth()->id(),
                ]);

            }

            return redirect()->route('products.index')
            ->with('success', 'Producto creado correctamente.');
            }

            public function edit(Product $product)
            {
                return view('products.edit', compact('product'));
            }

            public function update(Request $request, Product $product)
            {
                $warehouseId = session('current_warehouse_id');

                $request->validate([
                    'name' => 'required|string|max:255',
                    'price' => 'required|numeric|min:0',
                    'description' => 'nullable|string|max:255',
                    'min_stock' => 'nullable|integer|min:0',
                ]);

                DB::transaction(function () use ($request, $product, $warehouseId) {

                    $product->update([
                        'name' => $request->name,
                        'price' => $request->price,
                        'description' => $request->description,
                    ]);

                    DB::table('product_warehouse')
                        ->where('product_id', $product->id)
                        ->where('warehouse_id', $warehouseId)
                        ->update([
                            'min_stock' => $request->min_stock ?? 0
                        ]);

                });

                return redirect()->route('products.index')
                    ->with('success', 'Producto actualizado.');
            }

            public function adjust(Product $product)
            {
                if ($product->company_id !== session('current_company_id')) {
                    abort(404);
                }

                return view('products.adjust', compact('product'));
            }

            public function processAdjustment(Request $request, Product $product)
            {
                $warehouseId = 1;

                $request->validate([
                    'quantity' => 'required|integer|not_in:0',
                    'reason'   => 'required|string|max:255',
                ]);

                $quantity = (int) $request->quantity;

                $pivot = DB::table('product_warehouse')
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->first();

                if (! $pivot) {
                    abort(404);
                }

                if (($pivot->stock + $quantity) < 0) {
                    return back()->withErrors('El ajuste dejaría el stock en negativo.');
                }

                DB::transaction(function () use ($product, $quantity, $request, $warehouseId) {

                InventoryMovement::create([
                    'company_id' => session('current_company_id'),
                    'product_id' => $product->id,
                    'warehouse_id'=> $warehouseId,
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
    if ($product->company_id !== session('current_company_id')) {
        abort(404);
    }

    $movements = $product->movements()
    ->where('warehouse_id', session('current_warehouse_id'))
    ->latest()
    ->paginate(10);

    return view('products.show', compact('product', 'movements'));
}


    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Producto eliminado.');
    }
}
