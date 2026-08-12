@extends('layouts.app')

@section('title', 'Movimientos — ' . $account->numero_cuenta)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $account->bank->nombre ?? '—' }} — {{ $account->numero_cuenta }}</h2>
            <p class="text-sm text-gray-500">Saldo actual: <span class="font-semibold text-gray-900">{{ $account->currency->symbol ?? '$' }}{{ number_format($account->saldo_actual, 2) }}</span></p>
        </div>
    </div>

    @if ($errors->any())
        <x-stockia.alert tone="danger">{{ $errors->first() }}</x-stockia.alert>
    @endif
    @if (session('success'))
        <x-stockia.alert tone="success">{{ session('success') }}</x-stockia.alert>
    @endif

    <form action="{{ route('bank-accounts.movements.store', $account) }}" method="POST"
          class="grid grid-cols-2 md:grid-cols-5 gap-3 items-end rounded-xl border bg-white p-4 shadow-sm">
        @csrf
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Fecha</label>
            <input type="date" name="fecha" value="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Tipo</label>
            <select name="tipo" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                <option value="deposito">Depósito</option>
                <option value="retiro">Retiro</option>
                <option value="cheque">Cheque</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Monto</label>
            <input type="number" step="0.01" min="0.01" name="monto" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">No. Cheque</label>
            <input type="text" name="numero_cheque" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Descripción</label>
            <input type="text" name="descripcion" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
        </div>
        <div class="col-span-2 md:col-span-5">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Registrar movimiento</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Fecha</th>
                    <th class="px-4 py-3 text-left">Tipo</th>
                    <th class="px-4 py-3 text-left">Descripción</th>
                    <th class="px-4 py-3 text-left">No. Cheque</th>
                    <th class="px-4 py-3 text-right">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($movements as $movement)
                    <tr>
                        <td class="px-4 py-3">{{ $movement->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 capitalize">{{ $movement->tipo }}</td>
                        <td class="px-4 py-3">{{ $movement->descripcion ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $movement->numero_cheque ?: '—' }}</td>
                        <td class="px-4 py-3 text-right font-medium {{ $movement->tipo === 'deposito' ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $movement->tipo === 'deposito' ? '+' : '-' }}${{ number_format($movement->monto, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">Sin movimientos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ route('cuentas.show', $account) }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver a la cuenta</a>
</div>
@endsection
