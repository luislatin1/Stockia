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
    <div class="border p-3 mb-2">
        Venta #{{ $sale->id }} - ${{ $sale->total }}
    </div>
@empty
    <p>No hay ventas aún.</p>
@endforelse

@endsection
