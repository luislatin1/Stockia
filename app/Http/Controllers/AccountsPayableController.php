<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountsPayableController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $providers = Provider::where('company_id', $companyId)
            ->withSum(['purchases as saldo_total' => function ($query) {
                $query->where('es_credito', true)->where('saldo_pendiente', '>', 0);
            }], 'saldo_pendiente')
            ->having('saldo_total', '>', 0)
            ->orderByDesc('saldo_total')
            ->get();

        return view('accounts-payable.index', compact('providers'));
    }

    public function show(Provider $provider)
    {
        $companyId = (int) session('current_company_id');

        if ((int) $provider->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $purchases = $provider->purchases()
            ->where('es_credito', true)
            ->where('saldo_pendiente', '>', 0)
            ->orderBy('fecha_vencimiento')
            ->get();

        return view('accounts-payable.show', compact('provider', 'purchases'));
    }
}
