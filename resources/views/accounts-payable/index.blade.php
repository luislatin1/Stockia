@extends('layouts.app')

@section('title', 'Cuentas por Pagar')

@section('content')

<div class="max-w-4xl">

    <x-table>
        <x-slot name="thead">
            <th class="px-4 py-3">Proveedor</th>
            <th class="px-4 py-3 text-right">Saldo pendiente</th>
            <th class="px-4 py-3 w-10"></th>
        </x-slot>

        @forelse ($providers as $provider)
            <tr>
                <td class="px-4 py-3 text-gray-900">{{ $provider->nombre }}</td>
                <td class="px-4 py-3 text-right font-semibold text-amber-600">${{ number_format($provider->saldo_total, 2) }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('accounts-payable.show', $provider) }}" class="text-indigo-600 hover:underline text-sm">Ver detalle</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="px-4 py-6 text-center text-gray-400">No hay cuentas por pagar pendientes.</td>
            </tr>
        @endforelse
    </x-table>

</div>

@endsection
