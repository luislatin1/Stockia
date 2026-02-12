@extends('layouts.app')

@section('title', 'Ajuste de Inventario')

@section('content')

<div class="bg-white p-6 rounded shadow max-w-lg">

    <h2 class="text-xl font-bold mb-4">
        Ajustar Stock: {{ $product->name }}
    </h2>

    <p class="mb-4 text-gray-600">
        Stock actual: <strong>{{ $product->stock }}</strong>
    </p>

    <form method="POST" action="{{ route('products.processAdjustment', $product) }}">
        @csrf

        <label class="block mb-2 text-sm">Cantidad (+ o -)</label>
        <input type="number" name="quantity"
            class="border p-2 w-full mb-4"
            placeholder="Ej: 5 o -3">

        <label class="block mb-2 text-sm">Motivo</label>
        <input type="text" name="reason"
            class="border p-2 w-full mb-4"
            placeholder="Ej: Conteo físico">

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Aplicar Ajuste
        </button>
    </form>

</div>

@endsection
