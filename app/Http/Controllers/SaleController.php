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
        $companyId = session('current_company_id');
        $warehouseId = session('current_warehouse_id');
            $sales = Sale::where('company_id', $companyId)
                ->where('warehouse_id', $warehouseId)
                ->latest()
                ->get();

        
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

    public function edit(Sale $sale)
{
    if ($sale->status !== 'pending') {
        return redirect()->route('sales.index')
            ->withErrors('No se puede editar una venta completada o cancelada.');
    }

    return view('sales.edit', compact('sale'));
}

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $warehouseId = session('current_warehouse_id');

    

        DB::transaction(function () use ($request, $warehouseId) {

            $sale = Sale::create([
                'company_id' => session('current_company_id'),
                'warehouse_id' => $warehouseId,
                'user_id'    => auth()->id(),
                'status'     => 'completed',
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
                    'reference_type' => 'sale',
                    'reference_id'   => $sale->id,
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

    public function cancel(Sale $sale)
    {
        if ($sale->company_id !== session('current_company_id')) {
            abort(403);
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