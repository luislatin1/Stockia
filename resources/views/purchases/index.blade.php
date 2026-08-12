<?php // resources/views/purchases/index.blade.php ?>
@extends('layouts.app')

@section('title', 'Compras')

@section('topbar-actions')
    <a href="{{ route('purchases.create') }}"
       class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
        ＋ Nueva Compra
    </a>
@endsection

@section('content')

<div class="mb-4 flex items-center gap-2 border-b border-gray-200">
    <a href="{{ route('purchases.index') }}"
       class="px-3 py-2 text-sm font-medium border-b-2 {{ $view === 'live' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        Nuevas
    </a>
    <a href="{{ route('purchases.index', ['view' => 'historico']) }}"
       class="px-3 py-2 text-sm font-medium border-b-2 {{ $view === 'historico' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        Histórico (pos)
    </a>
</div>

@if($view === 'historico')
    <p class="text-xs text-gray-400 mb-3">Compras importadas del sistema anterior — solo consulta, no se pueden editar.</p>
@endif

<x-table>

    <x-slot name="thead">
        <th class="px-4 py-3">Fecha</th>
        <th class="px-4 py-3">Proveedor</th>
        <th class="px-4 py-3">No. Documento</th>
        <th class="px-4 py-3 text-right">Total</th>
        @if($view === 'live')
            <th class="px-4 py-3 text-right">Acciones</th>
        @endif
    </x-slot>

    @forelse($purchases as $purchase)
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-gray-900">{{ \Illuminate\Support\Carbon::parse($purchase->fecha)->format('d/m/Y') }}</td>
            <td class="px-4 py-3 text-gray-900">{{ $purchase->provider->nombre ?? '—' }}</td>
            <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $purchase->numero_doc ?: '—' }}</td>
            <td class="px-4 py-3 text-right font-medium text-gray-900">${{ number_format($purchase->total, 2) }}</td>
            @if($view === 'live')
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('purchases.show', $purchase) }}"
                       class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Ver
                    </a>
                </td>
            @endif
        </tr>
    @empty
        <tr>
            <td colspan="{{ $view === 'live' ? 5 : 4 }}" class="px-4 py-10 text-center text-sm text-gray-400">
                {{ $view === 'live' ? 'No hay compras registradas todavía.' : 'No hay compras históricas.' }}
            </td>
        </tr>
    @endforelse

    <x-slot name="footer">
        {{ $purchases->appends(['view' => $view])->links() }}
    </x-slot>

</x-table>

@endsection
