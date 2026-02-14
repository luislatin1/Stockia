<div>
@extends('layouts.app')
@section('title', 'Detalle de Venta')
@section('content')
<div class="bg-white p-6 rounded shadow max-w-3xl mx-auto">
    <h2 class="text-xl font-bold mb-4">Venta #{{ $sale->id }}</h2>
    <p class="mb-4 text-gray-600">Fecha: {{ $sale->created_at->format('d/m/Y H:i') }}</p>

    <table class="w-full mb-5">
        <thead>
            <tr>
                <th class="text-left border-b p-2">Producto</th>
                <th class="text-right border-b p-2">Cantidad</th>
                <th class="text-right border-b p-2">Precio Unitario</th>
                <th class="text-right border-b p-2">Subtotal</th>
                <th class="text-right border-b p-2">Estado</th>
                <th class="text-right border-b p-2">Acciones</th>
            </tr>  
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td class="border-b p-2">{{ $item->product->name }}</td>
                <td class="border-b p-2 text-right">{{ $item->quantity }}</td>
                <td class="border-b p-2 text-right">${{ number_format($item->unit_price, 2) }}</td>
                <td class="border-b p-2 text-right">${{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                <td class="border-b p-2 text-right">
                    @if($sale->status === 'cancelled')
                        <span class="text-red-600 font-bold">Cancelada</span>
                    @elseif($sale->status === 'pending')
                        <span class="text-yellow-600 font-bold">Pendiente</span>
                    @else
                        <span class="text-green-600 font-bold">Activa</span>
                    @endif
                </td>
                <td class="border-b p-2 text-right">
                    @if($sale->status === 'completed')
                        <form method="POST" action="{{ route('sales.cancel', $sale) }}">
                            @csrf
                            <button 
                                onclick="return confirm('¿Seguro que deseas cancelar esta venta?')"
                                class="bg-red-600 text-white px-3 py-1 rounded">
                                Cancelar
                            </button>
                         </form>
                        @else 
                        <span class="text-gray-500">No disponible</span>
                    @endif                    
                </td>

            </tr>
            @endforeach
        </tbody>
    </table>   
    <p class="text-right font-bold text-lg">Total: ${{ number_format($sale->total, 2) }}</p>
</div>
@endsection

