@extends('layouts.app')

@section('title', 'Movimientos de Inventario')

@section('content')

<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-xl font-bold">Historial de Movimientos</h2>
        <p class="text-sm text-gray-500">
            Mostrando almacén ID: {{ $selectedWarehouseId }}
        </p>
    </div>

    @if($canFilterByWarehouse)
        <form method="GET" action="{{ route('inventory_movements.index') }}" class="flex flex-wrap items-center gap-2">
            <label for="warehouse_id" class="text-sm text-gray-600">Almacén</label>
            <select id="warehouse_id" name="warehouse_id" class="rounded border border-gray-300 px-3 py-2 text-sm">
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" {{ (int) $selectedWarehouseId === (int) $warehouse->id ? 'selected' : '' }}>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
            <select name="type" class="rounded border border-gray-300 px-3 py-2 text-sm">
                <option value="">Tipo: Todos</option>
                <option value="in" {{ ($validated['type'] ?? '') === 'in' ? 'selected' : '' }}>Entradas</option>
                <option value="out" {{ ($validated['type'] ?? '') === 'out' ? 'selected' : '' }}>Salidas</option>
            </select>
            <input type="text" name="reference_type" value="{{ $validated['reference_type'] ?? '' }}" placeholder="Referencia: sale, adjustment..." class="rounded border border-gray-300 px-3 py-2 text-sm">
            <button class="rounded bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Filtrar
            </button>
        </form>
    @else
        <form method="GET" action="{{ route('inventory_movements.index') }}" class="flex flex-wrap items-center gap-2">
            <select name="type" class="rounded border border-gray-300 px-3 py-2 text-sm">
                <option value="">Tipo: Todos</option>
                <option value="in" {{ ($validated['type'] ?? '') === 'in' ? 'selected' : '' }}>Entradas</option>
                <option value="out" {{ ($validated['type'] ?? '') === 'out' ? 'selected' : '' }}>Salidas</option>
            </select>
            <input type="text" name="reference_type" value="{{ $validated['reference_type'] ?? '' }}" placeholder="Referencia: sale, adjustment..." class="rounded border border-gray-300 px-3 py-2 text-sm">
            <button class="rounded bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Filtrar
            </button>
        </form>
    @endif
</div>

<div class="bg-white shadow rounded overflow-hidden">

    <table class="min-w-full text-sm">
        <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
                <th class="px-4 py-3 text-left">Fecha</th>
                <th class="px-4 py-3 text-left">Almacén</th>
                <th class="px-4 py-3 text-left">Producto</th>
                <th class="px-4 py-3 text-left">Usuario</th>
                <th class="px-4 py-3 text-left">Tipo</th>
                <th class="px-4 py-3 text-left">Cantidad</th>
                <th class="px-4 py-3 text-left">Referencia</th>
                <th class="px-4 py-3 text-left">Motivo</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200">
            @foreach($movements as $movement)
            <tr>
                <td class="px-4 py-2">
                    {{ $movement->created_at->format('d/m/Y H:i') }}
                </td>

                <td class="px-4 py-2">
                    {{ $movement->warehouse->name ?? 'N/A' }}
                </td>

                <td class="px-4 py-2">
                    {{ $movement->product->name }}
                </td>
                <td class="px-4 py-2">
                    {{ $movement->user->name ?? 'N/A' }}
                </td>

                <td class="px-4 py-2">
                    @if($movement->type === 'in')
                        <span class="text-green-600 font-semibold">Entrada</span>
                    @else
                        <span class="text-red-600 font-semibold">Salida</span>
                    @endif
                </td>
                <td class="px-4 py-2">
                    {{ $movement->quantity }}
                </td>

                <td class="px-4 py-2 text-gray-500">
                    {{ $movement->reference_type }} #{{ $movement->reference_id }}
                </td>

                <td class="px-4 py-2">
                    {{ $movement->reason ?? '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>

<div class="mt-4">
    {{ $movements->links() }}
</div>

@endsection
