@extends('layouts.app')

@section('title', 'Lotes y Vencimientos')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Lotes y Vencimientos</h2>
            <p class="text-sm text-gray-500">Control de lotes registrados por producto y bodega.</p>
        </div>
        <a href="{{ route('product-batches.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Nuevo lote
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
        <select name="estado" onchange="this.form.submit()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Todos los estados</option>
            <option value="vigente" @selected(request('estado') === 'vigente')>Vigente</option>
            <option value="por_vencer" @selected(request('estado') === 'por_vencer')>Por vencer (30 días)</option>
            <option value="vencido" @selected(request('estado') === 'vencido')>Vencido</option>
        </select>
    </form>

    <x-table>
        <x-slot name="thead">
            <th class="px-4 py-3">Producto</th>
            <th class="px-4 py-3">Bodega</th>
            <th class="px-4 py-3">Lote</th>
            <th class="px-4 py-3">Vence</th>
            <th class="px-4 py-3 text-right">Cantidad</th>
            <th class="px-4 py-3">Estado</th>
            <th class="px-4 py-3 w-10"></th>
        </x-slot>

        @forelse ($batches as $batch)
            <tr>
                <td class="px-4 py-3 text-gray-900">{{ $batch->product->name ?? '—' }}</td>
                <td class="px-4 py-3">{{ $batch->warehouse->name ?? '—' }}</td>
                <td class="px-4 py-3 font-mono text-xs">{{ $batch->codigo_lote }}</td>
                <td class="px-4 py-3">{{ $batch->fecha_vencimiento->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-right">{{ $batch->cantidad }}</td>
                <td class="px-4 py-3">
                    @if ($batch->estado_vencimiento === 'vencido')
                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">Vencido</span>
                    @elseif ($batch->estado_vencimiento === 'por_vencer')
                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Por vencer</span>
                    @else
                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Vigente</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <form action="{{ route('product-batches.destroy', $batch) }}" method="POST" onsubmit="return confirm('¿Eliminar este lote?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline text-sm">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-6 text-center text-gray-500">No hay lotes registrados.</td>
            </tr>
        @endforelse

        <x-slot name="footer">
            {{ $batches->links() }}
        </x-slot>
    </x-table>
</div>
@endsection
