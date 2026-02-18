@extends('layouts.app')

@section('title', 'Editar Producto')

@section('content')
<form method="POST"
      action="{{ route('products.update', $product) }}"
      class="bg-white p-6 rounded shadow max-w-xl">

    @csrf
    @method('PUT')

    <div class="mb-4">
        <label>Nombre</label>
        <input type="text"
               name="name"
               value="{{ old('name', $product->name) }}"
               class="w-full border p-2 rounded">
    </div>

    <div class="mb-4">
        <label>SKU</label>
        <input type="text"
               name="sku"
               value="{{ old('sku', $product->sku) }}"
               class="w-full border p-2 rounded">
        @error('sku')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-4">
        <label>Código de barras</label>
        <input type="text"
               name="barcode"
               value="{{ old('barcode', $product->barcode) }}"
               class="w-full border p-2 rounded">
        @error('barcode')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
    </div>

    <label class="block text-sm mb-2">Stock mínimo</label>
    <input type="number"
           name="min_stock"
           value="{{ old('min_stock', $product->min_stock ?? 0) }}"
           class="border p-2 w-full mb-4">

    <div class="mb-4">
        <label>Precio</label>
        <input type="number"
               step="0.01"
               name="price"
               value="{{ old('price', $product->price) }}"
               class="w-full border p-2 rounded">
    </div>

    <button type="submit"
            class="bg-blue-600 text-white px-4 py-2 rounded">
        Actualizar
    </button>

</form>

@endsection
