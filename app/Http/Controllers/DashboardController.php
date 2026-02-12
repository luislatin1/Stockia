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
    $companyId = auth()->user()->company_id;

    $todaySales = Sale::where('company_id', $companyId)
        ->whereDate('created_at', Carbon::today())
        ->sum('total');

    $monthSales = Sale::where('company_id', $companyId)
        ->whereMonth('created_at', Carbon::now()->month)
        ->sum('total');

    $totalSales = Sale::where('company_id', $companyId)
        ->sum('total');

    $lowStock = Product::where('company_id', $companyId)
        ->where('stock', '<=', 5)
        ->get();

    $outOfStock = Product::where('company_id', $companyId)
        ->where('stock', 0)
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
