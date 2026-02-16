<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function index()
{
    $companyId = session('current_company_id');
    $warehouseId = session('current_warehouse_id');

        $todaySales = Sale::where('status', 'completed')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->whereDate('created_at', today())
            ->sum('total');

        $monthSales = Sale::where('status', 'completed')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $totalSalesCount = Sale::where('status', 'completed')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->count();

        $averageTicket = Sale::where('status', 'completed')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->avg('total');

        $topProducts = SaleItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->with('product')
            ->whereHas('sale', function ($query) use ($companyId, $warehouseId) {
                $query->where('company_id', $companyId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('status', 'completed');
            })
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

    $last30Days = Sale::select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('SUM(total) as total')
    )
    ->where('status', 'completed')
    ->where('company_id', $companyId)
    ->where('warehouse_id', $warehouseId)
    ->where('created_at', '>=', Carbon::now()->subDays(30))
    ->groupBy('date')
    ->orderBy('date')
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
        ->where('warehouse_id', $warehouseId)
        ->where('status', 'completed')
        ->latest()
        ->take(5)
        ->get();

    $lowStockProducts = Product::lowStock($warehouseId)->count();

    $dates = [];
    $totals = [];

    $last30Days = Sale::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total) as total')
        )
            ->where('status', 'completed')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            $dates = $last30Days->pluck('date')->toArray();
            $totals = $last30Days->pluck('total')
            ->map(fn($v) => (float) $v)
            ->toArray();

    return view('dashboard.index', compact(
            'todaySales',
            'monthSales',
            'totalSalesCount',
            'averageTicket',
            'topProducts',
            'lowStockProducts',
            'outOfStock',
            'latestSales',
            'dates',
            'totals'
    ));
}

}
