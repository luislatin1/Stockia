<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductLocationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $query = ProductLocation::where('company_id', $companyId)->with(['product', 'warehouse']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->query('warehouse_id'));
        }

        $locations = $query->orderBy('estante')->get();
        $warehouses = Warehouse::where('company_id', $companyId)->orderBy('name')->get();

        return view('product-locations.index', compact('locations', 'warehouses'));
    }

    public function create()
    {
        $companyId = (int) session('current_company_id');

        $warehouses = Warehouse::where('company_id', $companyId)->orderBy('name')->get();
        $products = Product::where('company_id', $companyId)->orderBy('name')->get(['id', 'name', 'sku']);

        return view('product-locations.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'product_id' => ['required', Rule::exists('products', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'estante' => ['required', 'string', 'max:30'],
            'cantidad' => ['nullable', 'integer', 'min:0'],
        ]);

        $existsInWarehouse = DB::table('product_warehouse')
            ->where('product_id', $validated['product_id'])
            ->where('warehouse_id', $validated['warehouse_id'])
            ->exists();

        if (! $existsInWarehouse) {
            return back()->withErrors('Ese producto no está asignado a la bodega seleccionada.')->withInput();
        }

        ProductLocation::create([
            'company_id' => $companyId,
            ...$validated,
        ]);

        return redirect()->route('product-locations.index')->with('success', 'Ubicación asignada correctamente.');
    }

    public function destroy(ProductLocation $estante)
    {
        $companyId = (int) session('current_company_id');

        if ((int) $estante->company_id !== $companyId) {
            abort(404);
        }

        $estante->delete();

        return redirect()->route('product-locations.index')->with('success', 'Ubicación eliminada.');
    }
}
