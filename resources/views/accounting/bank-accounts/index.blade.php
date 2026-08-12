@extends('layouts.app')

@section('title', 'Cuentas Bancarias')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Cuentas Bancarias</h2>
            <p class="text-sm text-gray-500">Cuentas de la empresa y su saldo actual.</p>
        </div>
        <a href="{{ route('cuentas.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Nueva cuenta
        </a>
    </div>

    @if (session('success'))
        <x-stockia.alert tone="success">{{ session('success') }}</x-stockia.alert>
    @endif

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Banco</th>
                    <th class="px-4 py-3 text-left">No. Cuenta</th>
                    <th class="px-4 py-3 text-left">Tipo</th>
                    <th class="px-4 py-3 text-right">Saldo</th>
                    <th class="px-4 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($accounts as $account)
                    <tr>
                        <td class="px-4 py-3 text-gray-900">{{ $account->bank->nombre ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $account->numero_cuenta }}</td>
                        <td class="px-4 py-3 capitalize">{{ $account->tipo_cuenta }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ $account->currency->symbol ?? '$' }}{{ number_format($account->saldo_actual, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('cuentas.show', $account) }}" class="text-indigo-600 hover:underline text-sm">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay cuentas bancarias registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ route('bancos.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver a Bancos</a>
</div>
@endsection
