<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryMovement;

class InventoryMovementController extends Controller
{
    public function index()
{
        $movements = InventoryMovement::with('product')
        ->whereHas('product', function ($query) {
            $query->where('company_id', session('current_company_id'));
        })
        ->latest()
        ->paginate(15);

    return view('inventory_movements.index', compact('movements'));
}


}
