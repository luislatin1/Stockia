@extends('layouts.app')

@section('title', 'Cuentas por Cobrar')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Cuentas por Cobrar</h2>
        <p class="text-sm text-gray-500">Clientes con ventas a crédito y saldo pendiente.</p>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Cliente</th>
                    <th class="px-4 py-3 text-right">Saldo pendiente</th>
                    <th class="px-4 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-4 py-3 text-gray-900">{{ $customer->nombre }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-amber-600">${{ number_format($customer->saldo_total, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('accounts-receivable.show', $customer) }}" class="text-indigo-600 hover:underline text-sm">Ver detalle</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-500">No hay cuentas por cobrar pendientes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
