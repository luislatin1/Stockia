<?php

namespace Stockia\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Stockia\Accounting\Models\Bank;

class BankController extends Controller
{
    public function index()
    {
        $companyId = (int) session('current_company_id');

        $banks = Bank::where('company_id', $companyId)->withCount('accounts')->orderBy('nombre')->get();

        return view('accounting::banks.index', compact('banks'));
    }

    public function create()
    {
        return view('accounting::banks.create');
    }

    public function store(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
        ]);

        Bank::create([
            'company_id' => $companyId,
            'nombre' => $validated['nombre'],
        ]);

        return redirect()->route('bancos.index')->with('success', 'Banco agregado correctamente.');
    }

    public function destroy(Bank $banco)
    {
        $companyId = (int) session('current_company_id');

        if ((int) $banco->company_id !== $companyId) {
            abort(404);
        }

        if ($banco->accounts()->exists()) {
            return back()->withErrors('No puedes eliminar un banco con cuentas registradas.');
        }

        $banco->delete();

        return redirect()->route('bancos.index')->with('success', 'Banco eliminado.');
    }
}
