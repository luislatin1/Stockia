@extends('layouts.app')

@section('title', 'Traslado #' . $stockTransfer->id)

@section('content')

<div class="max-w-3xl space-y-6">

    @if (session('success'))
        <x-stockia.alert tone="success">{{ session('success') }}</x-stockia.alert>
    @endif
    @if ($errors->any())
        <x-stockia.alert tone="danger">{{ $errors->first() }}</x-stockia.alert>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-900">Traslado #{{ $stockTransfer->id }}</h2>
            @if ($stockTransfer->status === 'enviado')
                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">En tránsito</span>
            @elseif ($stockTransfer->status === 'recibido')
                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Recibido</span>
            @else
                <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-700">Anulado</span>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide">Origen</p>
                <p class="font-medium text-gray-900">{{ $stockTransfer->originWarehouse->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide">Destino</p>
                <p class="font-medium text-gray-900">{{ $stockTransfer->destinationWarehouse->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide">Fecha</p>
                <p class="font-medium text-gray-900">{{ $stockTransfer->fecha->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide">Enviado por</p>
                <p class="font-medium text-gray-900">{{ $stockTransfer->user->name ?? '—' }}</p>
            </div>
            @if ($stockTransfer->status === 'recibido')
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide">Recibido por</p>
                    <p class="font-medium text-gray-900">{{ $stockTransfer->receivedBy->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide">Recibido el</p>
                    <p class="font-medium text-gray-900">{{ optional($stockTransfer->received_at)->format('d/m/Y H:i') }}</p>
                </div>
            @endif
        </div>

        @if ($stockTransfer->notes)
            <p class="mt-4 text-sm text-gray-600">{{ $stockTransfer->notes }}</p>
        @endif
    </div>

    <x-table>
        <x-slot name="thead">
            <th class="px-4 py-3">Producto</th>
            <th class="px-4 py-3 text-right">Cantidad</th>
        </x-slot>

        @foreach ($stockTransfer->items as $item)
            <tr>
                <td class="px-4 py-3 text-gray-900">{{ $item->product->name ?? '—' }}</td>
                <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
            </tr>
        @endforeach
    </x-table>

    @if ($stockTransfer->status === 'enviado')
        <div class="flex items-center gap-3">
            <form action="{{ route('stock-transfers.receive', $stockTransfer) }}" method="POST">
                @csrf
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Confirmar recepción
                </button>
            </form>
            <form action="{{ route('stock-transfers.cancel', $stockTransfer) }}" method="POST" onsubmit="return confirm('¿Anular este traslado y devolver el stock a la bodega de origen?');">
                @csrf
                <button type="submit" class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
                    Anular
                </button>
            </form>
        </div>
        <p class="text-xs text-gray-500">Para confirmar la recepción debes tener seleccionada la bodega destino ({{ $stockTransfer->destinationWarehouse->name ?? '—' }}) como bodega actual.</p>
    @endif

    <a href="{{ route('stock-transfers.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver a Traslados</a>

</div>

@endsection
