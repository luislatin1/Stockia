<?php

namespace Stockia\Accounting\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Stockia\Accounting\Models\Bank;
use Stockia\Accounting\Models\BankAccount;

class BankAccountController extends Controller
{
    public function index()
    {
        $companyId = (int) session('current_company_id');

        $accounts = BankAccount::where('company_id', $companyId)->with(['bank', 'currency'])->get();

        return view('accounting::bank-accounts.index', compact('accounts'));
    }

    public function create()
    {
        $companyId = (int) session('current_company_id');

        $banks = Bank::where('company_id', $companyId)->orderBy('nombre')->get();
        $currencies = Currency::orderBy('code')->get();

        return view('accounting::bank-accounts.create', compact('banks', 'currencies'));
    }

    public function store(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'bank_id' => ['required', Rule::exists('banks', 'id')->where(fn ($q) => $q->where('company_id', $companyId))],
            'currency_id' => ['required', 'exists:currencies,id'],
            'numero_cuenta' => ['required', 'string', 'max:40'],
            'tipo_cuenta' => ['required', 'in:corriente,ahorro'],
        ]);

        $account = BankAccount::create([
            'company_id' => $companyId,
            'bank_id' => $validated['bank_id'],
            'currency_id' => $validated['currency_id'],
            'numero_cuenta' => $validated['numero_cuenta'],
            'tipo_cuenta' => $validated['tipo_cuenta'],
        ]);

        return redirect()->route('cuentas.show', $account)->with('success', 'Cuenta bancaria creada correctamente.');
    }

    public function show(BankAccount $cuenta)
    {
        $companyId = (int) session('current_company_id');

        if ((int) $cuenta->company_id !== $companyId) {
            abort(404);
        }

        $cuenta->load(['bank', 'currency']);

        return view('accounting::bank-accounts.show', ['account' => $cuenta]);
    }
}
