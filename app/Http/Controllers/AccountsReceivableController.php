<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountsReceivableController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $customers = Customer::where('company_id', $companyId)
            ->withSum(['sales as saldo_total' => function ($query) {
                $query->where('payment_method', 'credit')->where('saldo_pendiente', '>', 0);
            }], 'saldo_pendiente')
            ->having('saldo_total', '>', 0)
            ->orderByDesc('saldo_total')
            ->get();

        return view('accounts-receivable.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $companyId = (int) session('current_company_id');

        if ((int) $customer->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $sales = $customer->sales()
            ->where('payment_method', 'credit')
            ->where('saldo_pendiente', '>', 0)
            ->orderBy('fecha_vencimiento')
            ->get();

        return view('accounts-receivable.show', compact('customer', 'sales'));
    }
}
