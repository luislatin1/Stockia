@extends('layouts.app')

@section('title', 'Editar Producto')

@section('content')
<form class="bg-white p-6 rounded shadow max-w-xl">
    <div class="mb-4">
        <label>Nombre</label>
        <input type="text" value="Producto demo" class="w-full border p-2 rounded">
    </div>
    <label class="block text-sm mb-2">Stock mínimo</label>
<input type="number" name="min_stock"
       value="{{ old('min_stock', $product->min_stock ?? 0) }}"
       class="border p-2 w-full mb-4">
    <div class="mb-4">
        <label>Precio</label>
        <input type="number" value="10.00" class="w-full border p-2 rounded">
    </div>
    <button class="bg-blue-600 text-white px-4 py-2 rounded">Actualizar</button>
</form>
@endsection
