@extends('layouts.app')

@section('title', 'Detalle Producto')

@section('content')

<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="text-xl font-bold mb-2">{{ $product->name }}</h2>
    <p><strong>SKU:</strong> {{ $product->sku ?: '-' }}</p>
    <p><strong>Código de barras:</strong> {{ $product->barcode ?: '-' }}</p>
    <p><strong>Precio:</strong> ${{ number_format($product->price, 2) }}</p>
    <p><strong>Stock actual:</strong> {{ $product->stock }}</p>
</div>

<div class="bg-white rounded shadow overflow-hidden">
    <div class="px-4 py-3 bg-gray-100 font-semibold">
        Historial de Movimientos
    </div>

    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
            <tr>
                <th class="px-4 py-2 text-left">Fecha</th>
                <th class="px-4 py-2 text-left">Tipo</th>
                <th class="px-4 py-2 text-left">Cantidad</th>
                <th class="px-4 py-2 text-left">Referencia</th>
                <th class="px-4 py-2 text-left">Comentario</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200">
            @foreach($movements as $movement)
            <tr>
                <td class="px-4 py-2">
                    {{ $movement->created_at->format('d/m/Y H:i') }}
                </td>

                <td class="px-4 py-2">
                    @if($movement->type === 'in')
                        <span class="text-green-600">Entrada</span>
                    @elseif($movement->type === 'out')
                        <span class="text-red-600">Salida</span>
                    @else
                        <span class="text-blue-600">Ajuste</span>
                    @endif
                </td>

                <td class="px-4 py-2 font-medium">
                    {{ $movement->quantity }}
                </td>

                <td class="px-4 py-2 text-gray-500">
                    {{ $movement->reference_label }}
                </td>

                <td class="px-4 py-2 text-gray-500">
                    {{ $movement->comment }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>

<div class="mt-4">
    {{ $movements->links() }}
</div>

@endsection
