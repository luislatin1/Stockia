@extends('layouts.app')

@section('title', 'Venta de Artículos')

@section('content')
<div class="bg-white p-4 shadow rounded">
    <h2 class="text-xl font-bold mb-4">Artículos de la Venta #{{ $sale->id }}</h2>

    <table class="w-full mb-4">
        <thead>
            <tr>
                <th class="text-left border-b p-2">Producto</th>
                <th class="text-right border-b p-2">Cantidad</th>
                <th class="text-right border-b p-2">Precio Unitario</th>
                <th class="text-right border-b p-2">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td class="border-b p-2">{{ $item->product->name }}</td>
                <td class="border-b p-2 text-right">{{ $item->quantity }}</td>
                <td class="border-b p-2 text-right">${{ number_format($item->unit_price, 2) }}</td>
                <td class="border-b p-2 text-right">${{ number_format($item->quantity * $item->unit_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>   
    <p class="text-right font-bold text-lg">Total: ${{ number_format($sale->total, 2) }}</p>   
    <a href="{{ route('sales.index') }}" class="text-blue-600">
        Volver a Ventas
    </a>
</div>
@endsection