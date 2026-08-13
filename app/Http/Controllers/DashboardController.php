<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');
        $warehouseId = session('current_warehouse_id');

        $salesStats = Sale::where('status', 'completed')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->selectRaw(
                'SUM(CASE WHEN DATE(created_at) = ? THEN total ELSE 0 END) as today_sales,
                SUM(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN total ELSE 0 END) as month_sales,
                COUNT(*) as total_sales_count,
                AVG(total) as average_ticket',
                [today()->toDateString(), now()->month, now()->year]
            )
            ->first();

        $todaySales = (float) $salesStats->today_sales;
        $monthSales = (float) $salesStats->month_sales;
        $totalSalesCount = (int) $salesStats->total_sales_count;
        $averageTicket = (float) $salesStats->average_ticket;

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
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = $last30Days->pluck('date')->toArray();
        $totals = $last30Days->pluck('total')
            ->map(fn ($v) => (float) $v)
            ->toArray();

        return view('dashboard.index', compact(
            'todaySales',
            'monthSales',
            'totalSalesCount',
            'averageTicket',
            'topProducts',
            'dates',
            'totals'
        ));
    }

}
