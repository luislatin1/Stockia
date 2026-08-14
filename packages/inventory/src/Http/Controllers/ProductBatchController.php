<?php

namespace Stockia\Inventory\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Stockia\Inventory\Models\ProductBatch;

class ProductBatchController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $query = ProductBatch::where('company_id', $companyId)->with(['product', 'warehouse']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->query('warehouse_id'));
        }

        $limitDate = now()->addDays(30)->toDateString();
        $today = now()->toDateString();

        match ($request->query('estado')) {
            'vencido' => $query->whereDate('fecha_vencimiento', '<', $today),
            'por_vencer' => $query->whereDate('fecha_vencimiento', '>=', $today)
                ->whereDate('fecha_vencimiento', '<=', $limitDate),
            'vigente' => $query->whereDate('fecha_vencimiento', '>', $limitDate),
            default => null,
        };

        $batches = $query->orderBy('fecha_vencimiento')->paginate(30)->withQueryString();

        $warehouses = Warehouse::where('company_id', $companyId)->orderBy('name')->get();

        return view('inventory::product-batches.index', compact('batches', 'warehouses'));
    }

    public function create()
    {
        $companyId = (int) session('current_company_id');

        $warehouses = Warehouse::where('company_id', $companyId)->orderBy('name')->get();
        $products = Product::where('company_id', $companyId)->orderBy('name')->get(['id', 'name', 'sku']);

        return view('inventory::product-batches.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'product_id' => ['required', Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'codigo_lote' => ['required', 'string', 'max:60'],
            'fecha_vencimiento' => ['required', 'date'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $existsInWarehouse = DB::table('product_warehouse')
            ->where('product_id', $validated['product_id'])
            ->where('warehouse_id', $validated['warehouse_id'])
            ->exists();

        if (! $existsInWarehouse) {
            return back()->withErrors('Ese producto no está asignado a la bodega seleccionada.')->withInput();
        }

        ProductBatch::create([
            'company_id' => $companyId,
            ...$validated,
        ]);

        return redirect()->route('product-batches.index')->with('success', 'Lote registrado correctamente.');
    }

    public function destroy(ProductBatch $lote)
    {
        $companyId = (int) session('current_company_id');

        if ((int) $lote->company_id !== $companyId) {
            abort(404);
        }

        $lote->delete();

        return redirect()->route('product-batches.index')->with('success', 'Lote eliminado.');
    }
}
