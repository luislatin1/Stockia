<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
{
    $companyId = session('current_company_id');
    $warehouseId = 1; // luego lo hacemos dinámico

    $todaySales = Sale::where('company_id', $companyId)
        ->whereDate('created_at', now())
        ->sum('total');

    $monthSales = Sale::where('company_id', $companyId)
        ->whereMonth('created_at', now()->month)
        ->sum('total');

    $totalSales = Sale::where('company_id', $companyId)
        ->sum('total');

    // 🔴 STOCK BAJO (comparando columnas pivot)
    $lowStock = Product::where('company_id', $companyId)
        ->whereHas('warehouses', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId)
              ->whereColumn('product_warehouse.stock', '<=', 'product_warehouse.min_stock');
        })
        ->with(['warehouses' => function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }])
        ->get();

    // ⚫ SIN STOCK
    $outOfStock = Product::where('company_id', $companyId)
        ->whereHas('warehouses', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId)
              ->where('product_warehouse.stock', 0);
        })
        ->with(['warehouses' => function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }])
        ->get();

    $latestSales = Sale::where('company_id', $companyId)
        ->latest()
        ->take(5)
        ->get();

    return view('dashboard.index', compact(
        'todaySales',
        'monthSales',
        'totalSales',
        'lowStock',
        'outOfStock',
        'latestSales'
    ));
}

}
