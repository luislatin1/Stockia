@extends('layouts.app')

@section('title', 'Ubicación en Estantes')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Ubicación en Estantes</h2>
            <p class="text-sm text-gray-500">Dónde está guardado cada producto dentro de cada bodega.</p>
        </div>
        <a href="{{ route('product-locations.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Asignar ubicación
        </a>
    </div>

    @if (session('success'))
        <x-stockia.alert tone="success">{{ session('success') }}</x-stockia.alert>
    @endif

    <form method="GET" class="flex flex-wrap items-center gap-2">
        <select name="warehouse_id" onchange="this.form.submit()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Todas las bodegas</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) request('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
        </select>
    </form>

    <x-table>
        <x-slot name="thead">
            <th class="px-4 py-3">Producto</th>
            <th class="px-4 py-3">Bodega</th>
            <th class="px-4 py-3">Estante</th>
            <th class="px-4 py-3 text-right">Cantidad</th>
            <th class="px-4 py-3 w-10"></th>
        </x-slot>

        @forelse ($locations as $location)
            <tr>
                <td class="px-4 py-3 text-gray-900">{{ $location->product->name ?? '—' }}</td>
                <td class="px-4 py-3">{{ $location->warehouse->name ?? '—' }}</td>
                <td class="px-4 py-3 font-mono text-xs">{{ $location->estante }}</td>
                <td class="px-4 py-3 text-right">{{ $location->cantidad ?? '—' }}</td>
                <td class="px-4 py-3 text-right">
                    <form action="{{ route('product-locations.destroy', $location) }}" method="POST" onsubmit="return confirm('¿Quitar esta ubicación?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline text-sm">Quitar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay ubicaciones asignadas.</td>
            </tr>
        @endforelse
    </x-table>
</div>
@endsection
