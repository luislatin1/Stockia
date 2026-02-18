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
    <div class="mb-3">
        <label>SKU</label>
        <input type="text" name="sku" class="border p-2 w-full"
               value="{{ old('sku') }}" placeholder="SKU interno">
        @error('sku')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
    </div>
    <div class="mb-3">
        <label>Código DTE</label>
        <input type="text" name="codigo" class="border p-2 w-full"
               value="{{ old('codigo') }}" placeholder="Código interno fiscal">
        @error('codigo')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
    </div>
    <div class="mb-3">
        <label>Código de barras</label>
        <input type="text" name="barcode" class="border p-2 w-full"
               value="{{ old('barcode') }}" placeholder="Escanea o escribe el código">
        @error('barcode')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
    </div>
<label class="block text-sm mb-2">Stock mínimo</label>
<input type="number" name="min_stock"
       value="{{ old('min_stock', 0) }}"
       class="border p-2 w-full mb-4">
    <div class="mb-3">
        <label>Tipo item</label>
        <select name="tipo_item" class="border p-2 w-full">
            <option value="1" @selected((string) old('tipo_item', '1') === '1')>Bien</option>
            <option value="2" @selected((string) old('tipo_item') === '2')>Servicio</option>
        </select>
    </div>
    <div class="mb-3">
        <label>Unidad de medida (CAT-014)</label>
        <input type="number" name="uni_medida" class="border p-2 w-full"
               value="{{ old('uni_medida', 59) }}">
    </div>
    <div class="mb-3">
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="afecto_iva" value="1" {{ old('afecto_iva', '1') ? 'checked' : '' }}>
            <span>Afecto IVA 13%</span>
        </label>
    </div>
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
