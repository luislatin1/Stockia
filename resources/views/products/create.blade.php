@extends('layouts.app')

@section('title', 'Crear Producto')

@section('content')

<h2 class="text-xl font-bold mb-4">Crear Producto</h2>

<form action="{{ route('products.store') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label>Nombre</label>
        <input type="text" name="name" class="border p-2 w-full"
               value="{{ old('name') }}">
        @error('name')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
    </div>
<label class="block text-sm mb-2">Stock mínimo</label>
<input type="number" name="min_stock"
       value="{{ old('min_stock', $product->min_stock ?? 0) }}"
       class="border p-2 w-full mb-4">
    <div class="mb-3">
        <label>Precio</label>
        <input type="number" step="0.01" name="price"
               class="border p-2 w-full"
               value="{{ old('price') }}">
        @error('price')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-3">
        <label>Stock</label>
        <input type="number" name="stock"
               class="border p-2 w-full"
               value="{{ old('stock') }}">
        @error('stock')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
    </div>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">
        Guardar
    </button>

</form>

@endsection
