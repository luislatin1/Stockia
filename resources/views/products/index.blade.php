@extends('layouts.app')

@section('title', 'Productos')

@section('topbar-actions')
    <a href="{{ route('products.create') }}"
       class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
        ＋ Nuevo Producto
    </a>
@endsection

@section('content')

{{-- ── STAT CARDS ──────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-2">📦 Total Productos</p>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        <p class="text-xs text-gray-400 mt-1">en inventario</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-2">⚠️ Stock Bajo</p>
        <p class="text-3xl font-bold {{ $stats['low_stock'] > 0 ? 'text-amber-500' : 'text-gray-900' }}">
            {{ $stats['low_stock'] }}
        </p>
        <p class="text-xs text-gray-400 mt-1">bajo mínimo</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-2">🚫 Sin Stock</p>
        <p class="text-3xl font-bold {{ $stats['out_of_stock'] > 0 ? 'text-red-600' : 'text-gray-900' }}">
            {{ $stats['out_of_stock'] }}
        </p>
        <p class="text-xs text-gray-400 mt-1">agotados</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-2">💰 Valor Inventario</p>
        <p class="text-3xl font-bold text-indigo-600">${{ number_format($stats['inventory_value'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">precio × unidades</p>
    </div>

</div>

{{-- ── TABLA ───────────────────────────────────────────────────── --}}
<x-table>

    {{-- Barra de búsqueda y filtros --}}
    <x-slot name="header">
        <form method="GET" action="{{ route('products.index') }}" class="flex items-center gap-2 flex-1 min-w-0">
            <input type="text"
                   name="q"
                   value="{{ $search ?? '' }}"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                   placeholder="Buscar por nombre, SKU o código…">
            <button type="submit"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                Buscar
            </button>
            @if($search)
                <a href="{{ route('products.index') }}"
                   class="text-sm text-gray-400 hover:text-gray-600">✕ Limpiar</a>
            @endif
        </form>

        <div class="flex items-center gap-2 shrink-0">
            @if(request('low_stock'))
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-100 transition-colors">
                    ✕ Quitar filtro
                </a>
            @else
                <a href="{{ route('products.index', ['low_stock' => 1]) }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    ⚠️ Stock bajo
                </a>
            @endif
        </div>
    </x-slot>

    {{-- Columnas --}}
    <x-slot name="thead">
        <th class="px-4 py-3">Nombre</th>
        <th class="px-4 py-3">SKU</th>
        <th class="px-4 py-3">Código Barras</th>
        <th class="px-4 py-3 text-right">Precio</th>
        <th class="px-4 py-3 text-right">Stock</th>
        <th class="px-4 py-3 text-right">Acciones</th>
    </x-slot>

    @forelse($products as $product)
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-medium text-gray-900">{{ $product->name }}</td>
            <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $product->sku ?: '—' }}</td>
            <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $product->barcode ?: '—' }}</td>
            <td class="px-4 py-3 text-right font-medium text-gray-900">${{ number_format($product->price, 2) }}</td>
            <td class="px-4 py-3 text-right">
                @if($product->stock === 0)
                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                        🚫 Agotado
                    </span>
                @elseif($product->stock <= $product->min_stock)
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                        ⚠ {{ $product->stock }}
                    </span>
                @else
                    <span class="font-medium text-gray-900">{{ $product->stock }}</span>
                @endif
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('products.edit', $product) }}"
                       class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Editar
                    </a>
                    <a href="{{ route('products.adjust', $product) }}"
                       class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Ajustar
                    </a>
                    <a href="{{ route('products.show', $product) }}"
                       class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Ver
                    </a>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">
                No hay productos que coincidan.
            </td>
        </tr>
    @endforelse

    {{-- Footer con contador --}}
    <x-slot name="footer">
        <span class="text-xs text-gray-400">
            {{ $products->count() }} de {{ $stats['total'] }} productos
            @if($search) · búsqueda: "<strong>{{ $search }}</strong>" @endif
            @if(request('low_stock')) · filtro: stock bajo @endif
        </span>
    </x-slot>

</x-table>

@endsection
