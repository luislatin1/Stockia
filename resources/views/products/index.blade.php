@extends('layouts.app')

@section('title', 'Productos')

@section('content')

<x-table>

    {{-- HEADER --}}
    <x-slot name="header">
        <a href="{{ route('products.create')}}" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">
            + Nuevo Producto
        </a>
    </x-slot>
<div class="mb-4">
    <a href="{{ route('products.index', ['low_stock' => 1]) }}"
       class="bg-red-600 text-white px-4 py-2 rounded text-sm">
        Ver Stock Bajo
    </a>

    <a href="{{ route('products.index') }}"
       class="ml-2 bg-gray-500 text-white px-3 py-2 rounded text-sm">
        Ver Todos
    </a>
</div>
    {{-- COLUMNAS --}}
    <x-slot name="thead">
        <th class="p-3">ID</th>
        <th class="p-3">Nombre</th>
        <th class="p-3">Precio</th>
        <th class="p-3">Stock</th>
        <th class="p-3 text-right">Acciones</th>
    </x-slot>

    @forelse($products as $product)
<tr class="hover:bg-gray-50">
    <td class="p-3">{{ $product->id }}</td>
    <td class="p-3">{{ $product->name }}</td>
    <td class="p-3">${{ number_format($product->price, 2) }}</td>
    <td class="px-4 py-2 font-medium">
        @if($product->stock <= $product->min_stock)
            <span class="text-red-600 font-bold">
                {{ $product->stock }} ⚠
            </span>
        @else
            {{ $product->stock }}
        @endif
    </td>
    <td class="p-3 text-right space-x-2">
        <a href="{{ route('products.edit', $product) }}" class="text-blue-600">
            Editar
        </a>

        <form action="{{ route('products.destroy', $product) }}"
              method="POST"
              class="inline">
            @csrf
            @method('DELETE')
            <button 
                class="text-red-600"
                onclick="return confirm('¿Estás seguro de eliminar este producto?')">
                Eliminar
            </button>
        </form>
        <a href="{{ route('products.adjust', $product) }}"
            class="text-blue-600 text-sm">
            Ajustar
        </a>
        <a href="{{ route('products.show', $product) }}"
        class="text-gray-600 text-sm">
        Ver Detalles
        </a>
    </td>
</tr>
@empty
<tr>
    <td colspan="5" class="p-3 text-center">
        No hay productos aún.
    </td>
</tr>
@endforelse

</x-table>

@endsection
