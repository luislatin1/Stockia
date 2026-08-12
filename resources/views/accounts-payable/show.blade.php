@extends('layouts.app')

@section('title', 'Cuentas por Pagar — ' . $provider->nombre)

@section('content')

<div class="max-w-4xl space-y-4">

    <div>
        <h2 class="text-base font-semibold text-gray-900">{{ $provider->nombre }}</h2>
        <p class="text-xs text-gray-400 mt-0.5">Compras a crédito con saldo pendiente.</p>
    </div>

    <x-table>
        <x-slot name="thead">
            <th class="px-4 py-3">Fecha</th>
            <th class="px-4 py-3">No. Documento</th>
            <th class="px-4 py-3">Vence</th>
            <th class="px-4 py-3 text-right">Saldo pendiente</th>
            <th class="px-4 py-3 w-10"></th>
        </x-slot>

        @forelse ($purchases as $purchase)
            <tr>
                <td class="px-4 py-3">{{ $purchase->fecha->format('d/m/Y') }}</td>
                <td class="px-4 py-3">{{ $purchase->numero_doc ?: '—' }}</td>
                <td class="px-4 py-3 {{ $purchase->esta_vencida ? 'text-red-600 font-medium' : '' }}">
                    {{ $purchase->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                    @if ($purchase->esta_vencida)
                        <span class="text-xs">(vencida)</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-semibold text-amber-600">${{ number_format($purchase->saldo_pendiente, 2) }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('purchases.show', $purchase) }}" class="text-indigo-600 hover:underline text-sm">Ver / Abonar</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-6 text-center text-gray-400">Este proveedor no tiene saldo pendiente.</td>
            </tr>
        @endforelse
    </x-table>

    <a href="{{ route('accounts-payable.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver a Cuentas por Pagar</a>

</div>

@endsection
