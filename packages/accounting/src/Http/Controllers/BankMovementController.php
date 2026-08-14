<?php

namespace Stockia\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Stockia\Accounting\Models\BankAccount;
use Stockia\Accounting\Models\BankMovement;

class BankMovementController extends Controller
{
    private function ensureAccess(BankAccount $bankAccount): void
    {
        if ((int) $bankAccount->company_id !== (int) session('current_company_id')) {
            abort(404);
        }
    }

    public function index(BankAccount $bankAccount)
    {
        $this->ensureAccess($bankAccount);

        $bankAccount->load(['bank', 'currency']);
        $movements = $bankAccount->movements()->with('user')->latest('fecha')->latest('id')->paginate(30);

        return view('accounting::bank-movements.index', ['account' => $bankAccount, 'movements' => $movements]);
    }

    public function store(Request $request, BankAccount $bankAccount)
    {
        $this->ensureAccess($bankAccount);

        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'tipo' => ['required', 'in:deposito,retiro,cheque'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'numero_cheque' => ['nullable', 'string', 'max:30'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['tipo'] !== 'deposito' && (float) $validated['monto'] > (float) $bankAccount->saldo_actual) {
            return back()->withErrors('El monto no puede ser mayor al saldo actual de la cuenta.')->withInput();
        }

        BankMovement::create([
            'company_id' => $bankAccount->company_id,
            'bank_account_id' => $bankAccount->id,
            'user_id' => auth()->id(),
            'fecha' => $validated['fecha'],
            'tipo' => $validated['tipo'],
            'monto' => $validated['monto'],
            'numero_cheque' => $validated['numero_cheque'] ?? null,
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        return redirect()->route('bank-accounts.movements.index', $bankAccount)
            ->with('success', 'Movimiento registrado correctamente.');
    }
}
