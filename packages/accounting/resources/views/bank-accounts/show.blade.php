@extends('layouts.app')

@section('title', 'Cuenta ' . $account->numero_cuenta)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ $account->bank->nombre ?? '—' }} — {{ $account->numero_cuenta }}</h2>
        <p class="text-sm text-gray-500 capitalize">Cuenta {{ $account->tipo_cuenta }} en {{ $account->currency->code ?? '—' }}</p>
    </div>

    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <p class="text-xs uppercase text-gray-500">Saldo actual</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $account->currency->symbol ?? '$' }}{{ number_format($account->saldo_actual, 2) }}</p>
    </div>

    <a href="{{ route('bank-accounts.movements.index', $account) }}" class="inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        Ver movimientos / Libro de banco
    </a>

    <div>
        <a href="{{ route('cuentas.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver a Cuentas Bancarias</a>
    </div>
</div>
@endsection
