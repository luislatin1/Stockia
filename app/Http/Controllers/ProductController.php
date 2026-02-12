<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
public function index(Request $request)
    {
    $query = Product::where('company_id', auth()->user()->company_id);

    if ($request->low_stock) {
        $query->whereColumn('stock', '<=', 'min_stock');
    }

    $products = $query->orderBy('name')->paginate(15);

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
        'stock' => $request->stock ?? 0,
        ]);

    if ($request->stock > 0) {
        $product->movements()->create([
            'type' => 'in',
            'quantity' => $request->stock,
            'reference' => 'Stock inicial',
        ]);
    };

        return redirect()->route('products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $product->update($request->only('name', 'price', 'stock'));

        return redirect()->route('products.index')
            ->with('success', 'Producto actualizado.');
    }

    public function adjust(Product $product)
{
    if ($product->company_id !== auth()->user()->company_id) {
        abort(404);
    }

    return view('products.adjust', compact('product'));
}

public function processAdjustment(Request $request, Product $product)
{
    if ($product->company_id !== auth()->user()->company_id) {
        abort(404);
    }

    $request->validate([
        'quantity' => 'required|integer|not_in:0',
        'reason'   => 'required|string|max:255',
    ]);

    $quantity = (int) $request->quantity;

    if (($product->stock + $quantity) < 0) {
        return back()->withErrors('El ajuste dejaría el stock en negativo.');
    }

    DB::transaction(function () use ($product, $quantity, $request) {

        $product->increment('stock', $quantity);

        InventoryMovement::create([
            'product_id' => $product->id,
            'type'       => 'adjustment',
            'quantity'   => $quantity,
            'reference'  => 'Ajuste: ' . $request->reason,
        ]);

    });

    return redirect()
        ->route('products.index')
        ->with('success', 'Stock ajustado correctamente.');
}

public function show(Product $product)
{
    if ($product->company_id !== auth()->user()->company_id) {
        abort(404);
    }

    $movements = $product->movements()->paginate(10);

    return view('products.show', compact('product', 'movements'));
}


    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Producto eliminado.');
    }
}
