@extends('layouts.app')

@section('title', 'Asignar Ubicación')

@section('content')
<div class="max-w-md mx-auto space-y-6">
    <h2 class="text-2xl font-bold text-gray-900">Asignar Ubicación</h2>

    <form action="{{ route('product-locations.store') }}" method="POST" class="rounded-xl border bg-white p-6 shadow-sm space-y-4">
        @csrf
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Bodega</label>
            <select name="warehouse_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Producto</label>
            <select name="product_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">El producto debe estar asignado a la bodega seleccionada.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Estante</label>
            <input type="text" name="estante" placeholder="A-01" value="{{ old('estante') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Cantidad (opcional)</label>
            <input type="number" min="0" name="cantidad" value="{{ old('cantidad') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Guardar</button>
            <a href="{{ route('product-locations.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
        </div>
    </form>
</div>
@endsection
