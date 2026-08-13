@extends('layouts.app')

@section('title', 'Traslados entre Bodegas')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Traslados entre Bodegas</h2>
            <p class="text-sm text-gray-500">Traslados enviados o recibidos por la bodega actual.</p>
        </div>
        <a href="{{ route('stock-transfers.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Nuevo traslado
        </a>
    </div>

    @if (session('success'))
        <x-stockia.alert tone="success">{{ session('success') }}</x-stockia.alert>
    @endif

    <x-table>
        <x-slot name="thead">
            <th class="px-4 py-3">Fecha</th>
            <th class="px-4 py-3">Origen</th>
            <th class="px-4 py-3">Destino</th>
            <th class="px-4 py-3">Estado</th>
            <th class="px-4 py-3 w-10"></th>
        </x-slot>

        @forelse ($transfers as $transfer)
            <tr>
                <td class="px-4 py-3">{{ $transfer->fecha->format('d/m/Y') }}</td>
                <td class="px-4 py-3">{{ $transfer->originWarehouse->name ?? '—' }}</td>
                <td class="px-4 py-3">{{ $transfer->destinationWarehouse->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    @if ($transfer->status === 'enviado')
                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">En tránsito</span>
                    @elseif ($transfer->status === 'recibido')
                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Recibido</span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-700">Anulado</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('stock-transfers.show', $transfer) }}" class="text-indigo-600 hover:underline text-sm">Ver</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay traslados registrados.</td>
            </tr>
        @endforelse

        <x-slot name="footer">
            {{ $transfers->links() }}
        </x-slot>
    </x-table>
</div>
@endsection
