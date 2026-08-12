@extends('layouts.app')

@section('title', 'Cuentas por Cobrar — ' . $customer->nombre)

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ $customer->nombre }}</h2>
        <p class="text-sm text-gray-500">Ventas a crédito con saldo pendiente.</p>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Venta</th>
                    <th class="px-4 py-3 text-left">Fecha</th>
                    <th class="px-4 py-3 text-left">Vence</th>
                    <th class="px-4 py-3 text-right">Saldo pendiente</th>
                    <th class="px-4 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($sales as $sale)
                    <tr>
                        <td class="px-4 py-3">#{{ $sale->numero_interno ?? $sale->id }}</td>
                        <td class="px-4 py-3">{{ $sale->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 {{ $sale->esta_vencida ? 'text-red-600 font-medium' : '' }}">
                            {{ $sale->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                            @if ($sale->esta_vencida)
                                <span class="text-xs">(vencida)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-amber-600">${{ number_format($sale->saldo_pendiente, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('sales.show', $sale) }}" class="text-indigo-600 hover:underline text-sm">Ver / Cobrar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">Este cliente no tiene saldo pendiente.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ route('accounts-receivable.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver a Cuentas por Cobrar</a>
</div>
@endsection
