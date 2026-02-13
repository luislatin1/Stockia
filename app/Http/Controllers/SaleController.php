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
        $sales = Sale::latest()->get();

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $products = Product::with('warehouses')->get();

        return view('sales.create', compact('products'));
    }

    public function show($id)
    {
        $sale = Sale::with('items.product')->findOrFail($id);

        return view('sales.show', compact('sale'));
    }

    public function edit($id)
    {
        $sale = Sale::with('items.product')->findOrFail($id);

        return view('sales.edit', compact('sale'));
    }

    public function store(Request $request)
    {
        $warehouseId = session('current_warehouse_id') ?? 1;

        DB::transaction(function () use ($request, $warehouseId) {

            $sale = Sale::create([
                'company_id' => session('current_company_id'),
                'user_id'    => auth()->id(),
                'total'      => 0
            ]);

            $total = 0;

            foreach ($request->products as $productId => $quantity) {

                if ($quantity <= 0) {
                    continue;
                }

                $product = Product::findOrFail($productId);

                // 🔥 Validación y descuento centralizado
                $product->removeStock($warehouseId, $quantity);

                $subtotal = $product->price * $quantity;

                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                    'price'      => $product->price,
                    'subtotal'   => $subtotal,
                ]);

                InventoryMovement::create([
                    'company_id'   => session('current_company_id'),
                    'warehouse_id' => $warehouseId,
                    'product_id'   => $product->id,
                    'type'         => 'out',
                    'quantity'     => $quantity,
                    'reference'    => 'Venta #' . $sale->id,
                    'user_id'      => auth()->id(),
                ]);

                $total += $subtotal;
            }

            $sale->update([
                'total' => $total
            ]);
        });

        return redirect()->route('sales.index')
            ->with('success', 'Venta registrada correctamente');
    }
}