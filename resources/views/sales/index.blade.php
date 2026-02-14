@extends('layouts.app')

@section('title', 'Ventas')

@section('content')

<h2 class="text-xl font-bold mb-4">Ventas</h2>

@section('boton-accion')
    <a href="create.blade.php"px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
        +Nueva Venta
    </a>
@endsection

@forelse($sales as $sale)
    <div class="bg-white p-4 rounded shadow mb-4">
        <div class="flex justify-between items-center mb-2">
            <div>
                <strong>Venta #{{ $sale->id }}</strong>
                <span class="text-gray-600 text-sm">({{ $sale->created_at->format('d/m/Y H:i') }})</span>
            </div>
            <a href="{{ route('sales.show', $sale) }}" class="text-blue-600">
                Ver Detalles
            </a>
        </div>
        <p>Total: ${{ number_format($sale->total, 2) }}</p>
    </div>
@empty
    <p>No hay ventas aún.</p>
@endforelse

@endsection
