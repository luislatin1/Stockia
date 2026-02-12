<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SaleController extends Controller
{
    public function index()
{
    $sales = Sale::where('company_id', session('current_company_id'))
                ->latest()
                ->get();

    return view('sales.index', compact('sales'));
}
    public function create()
    {
        $products = Product::where('company_id', session('current_company_id'))->get();

        return view('sales.create', compact('products'));
    }

public function show($id)
{
    $sale = Sale::with('items.product')
                ->where('company_id', session('current_company_id'))
                ->findOrFail($id);

    return view('sales.show', compact('sale'));
}

public function edit($id)
{
    $sale = Sale::with('items.product')
                ->where('company_id', session('current_company_id'))
                ->findOrFail($id);

    return view('sales.edit', compact('sale'));
}

public function store(Request $request)
{
    DB::transaction(function () use ($request) {

        $sale = Sale::create([
            'company_id' => session('current_company_id'),
            'user_id'    => auth()->user()->id,
            'total'      => 0
        ]);

        $total = 0;

        foreach ($request->products as $productId => $quantity) {

            if ($quantity > 0) {

                $product = Product::findOrFail($productId);

// 🔥 VALIDAR ANTES DE TODO
if ($quantity > $product->stock) {
    throw new \Exception("Stock insuficiente para {$product->name}");
}

$subtotal = $product->price * $quantity;

// Crear detalle
SaleItem::create([
    'sale_id'    => $sale->id,
    'product_id' => $product->id,
    'quantity'   => $quantity,
    'price'      => $product->price,
    'subtotal'   => $subtotal,
]);

// Descontar stock
$product->decrement('stock', $quantity);

// Registrar movimiento
InventoryMovement::create([
    // 'company_id' => session('current_company_id'),
    'product_id' => $product->id,
    'type'       => 'out',
    'quantity'   => $quantity,
    'reference'  => 'Venta #' . $sale->id,
]);


                $total += $subtotal;
            }
        }

        $sale->update([
            'total' => $total
        ]);
    });

    return redirect()->route('sales.index')
        ->with('success', 'Venta registrada correctamente');
}
}
