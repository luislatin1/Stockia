@extends('layouts.app')

@section('title', 'Nueva Venta')

@section('content')

<h2 class="text-xl font-bold mb-4">Nueva Venta</h2>

<form action="{{ route('sales.store') }}" method="POST">
    @csrf

    @foreach($products as $product)
        <div class="mb-2">
            <label>
                {{ $product->name }}
                (Stock: {{ $product->stock }})
            </label>

            <input type="number"
                   name="products[{{ $product->id }}]"
                   min="0"
                   class="border p-1 w-20">
        </div>
    @endforeach

    <button class="bg-green-600 text-white px-4 py-2 mt-4 rounded">
        Confirmar Venta
    </button>
</form>

@endsection
