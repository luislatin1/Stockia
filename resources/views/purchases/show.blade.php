@extends('layouts.app')

@section('title', 'Compra #' . $purchase->id)

@section('content')

<div class="max-w-3xl space-y-6">

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide">Fecha</p>
                <p class="font-medium text-gray-900">{{ \Illuminate\Support\Carbon::parse($purchase->fecha)->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide">Proveedor</p>
                <p class="font-medium text-gray-900">{{ $purchase->provider->nombre ?? '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide">No. Documento</p>
                <p class="font-medium text-gray-900">{{ $purchase->numero_doc ?: '—' }}</p>
            </div>
        </div>
        @if($purchase->notes)
            <p class="mt-4 text-sm text-gray-600">{{ $purchase->notes }}</p>
        @endif
    </div>

    <x-table>
        <x-slot name="thead">
            <th class="px-4 py-3">Producto</th>
            <th class="px-4 py-3 text-right">Cantidad</th>
            <th class="px-4 py-3 text-right">Costo unitario</th>
            <th class="px-4 py-3 text-right">Subtotal</th>
        </x-slot>

        @foreach ($purchase->items as $item)
            <tr>
                <td class="px-4 py-3 text-gray-900">{{ $item->product->name ?? '—' }}</td>
                <td class="px-4 py-3 text-right">{{ $item->cantidad }}</td>
                <td class="px-4 py-3 text-right">${{ number_format($item->costo_unitario, 2) }}</td>
                <td class="px-4 py-3 text-right font-medium">${{ number_format($item->subtotal, 2) }}</td>
            </tr>
        @endforeach

        <x-slot name="footer">
            <div class="flex justify-end text-sm font-semibold text-gray-900">
                Total: ${{ number_format($purchase->total, 2) }}
            </div>
        </x-slot>
    </x-table>

    <a href="{{ route('purchases.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver a Compras</a>

</div>

@endsection
