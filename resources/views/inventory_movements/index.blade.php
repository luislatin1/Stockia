@extends('layouts.app')

@section('title', 'Movimientos de Inventario')

@section('content')

@php
    $motivoLabel = function ($m) {
        if ($m->reason) return $m->reason;
        return match ($m->reference_type) {
            'initial'                  => 'Carga inicial de inventario',
            'sale'                     => 'Descuento por venta',
            'adjustment'               => 'Ajuste manual',
            'sale_adjustment_restock'  => 'Reposición por devolución',
            'pos_stock_adjustment'     => 'Ajuste de stock en POS',
            default                    => $m->reference_type ?: '—',
        };
    };

    $refLabel = function ($m) {
        if (! $m->reference_type) return '—';
        $label = match ($m->reference_type) {
            'initial'                  => 'Inicial',
            'sale'                     => 'Venta',
            'adjustment'               => 'Ajuste manual',
            'sale_adjustment_restock'  => 'Devolución venta',
            'pos_stock_adjustment'     => 'Ajuste POS',
            default                    => $m->reference_type,
        };
        return $m->reference_id ? $label . ' #' . $m->reference_id : $label;
    };

    $typeIcon = fn ($t) => $t === 'in' ? '📥' : '📤';
    $typeLabel = fn ($t) => $t === 'in' ? 'Entrada' : 'Salida';
@endphp

{{-- ── FILTROS ─────────────────────────────────────────────────── --}}
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

    @if($canFilterByWarehouse)
        <form method="GET" action="{{ route('inventory_movements.index') }}"
              class="flex flex-wrap items-center gap-2">
            <select id="warehouse_id" name="warehouse_id"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ (int) $selectedWarehouseId === (int) $wh->id ? 'selected' : '' }}>
                        {{ $wh->name }}
                    </option>
                @endforeach
            </select>
            <select name="type"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">Todos los tipos</option>
                <option value="in"  {{ ($validated['type'] ?? '') === 'in'  ? 'selected' : '' }}>📥 Entradas</option>
                <option value="out" {{ ($validated['type'] ?? '') === 'out' ? 'selected' : '' }}>📤 Salidas</option>
            </select>
            <select name="reference_type"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">Todas las referencias</option>
                <option value="sale"                    {{ ($validated['reference_type'] ?? '') === 'sale'                    ? 'selected' : '' }}>Ventas</option>
                <option value="initial"                 {{ ($validated['reference_type'] ?? '') === 'initial'                 ? 'selected' : '' }}>Stock inicial</option>
                <option value="adjustment"              {{ ($validated['reference_type'] ?? '') === 'adjustment'              ? 'selected' : '' }}>Ajustes manuales</option>
                <option value="sale_adjustment_restock" {{ ($validated['reference_type'] ?? '') === 'sale_adjustment_restock' ? 'selected' : '' }}>Devoluciones</option>
                <option value="pos_stock_adjustment"    {{ ($validated['reference_type'] ?? '') === 'pos_stock_adjustment'    ? 'selected' : '' }}>Ajustes POS</option>
            </select>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                Filtrar
            </button>
            @if(($validated['type'] ?? '') || ($validated['reference_type'] ?? '') || $selectedWarehouseId !== $currentWarehouseId)
                <a href="{{ route('inventory_movements.index') }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    ✕ Limpiar
                </a>
            @endif
        </form>
    @else
        <form method="GET" action="{{ route('inventory_movements.index') }}"
              class="flex flex-wrap items-center gap-2">
            <select name="type"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">Todos los tipos</option>
                <option value="in"  {{ ($validated['type'] ?? '') === 'in'  ? 'selected' : '' }}>📥 Entradas</option>
                <option value="out" {{ ($validated['type'] ?? '') === 'out' ? 'selected' : '' }}>📤 Salidas</option>
            </select>
            <select name="reference_type"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <option value="">Todas las referencias</option>
                <option value="sale"                    {{ ($validated['reference_type'] ?? '') === 'sale'                    ? 'selected' : '' }}>Ventas</option>
                <option value="initial"                 {{ ($validated['reference_type'] ?? '') === 'initial'                 ? 'selected' : '' }}>Stock inicial</option>
                <option value="adjustment"              {{ ($validated['reference_type'] ?? '') === 'adjustment'              ? 'selected' : '' }}>Ajustes manuales</option>
                <option value="sale_adjustment_restock" {{ ($validated['reference_type'] ?? '') === 'sale_adjustment_restock' ? 'selected' : '' }}>Devoluciones</option>
                <option value="pos_stock_adjustment"    {{ ($validated['reference_type'] ?? '') === 'pos_stock_adjustment'    ? 'selected' : '' }}>Ajustes POS</option>
            </select>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                Filtrar
            </button>
        </form>
    @endif

    <span class="text-xs text-gray-400 shrink-0">
        {{ $movements->total() }} registros · página {{ $movements->currentPage() }} de {{ $movements->lastPage() }}
    </span>
</div>

{{-- ── TABLA ───────────────────────────────────────────────────── --}}
<x-table>

    <x-slot name="thead">
        <th class="px-4 py-3">Fecha</th>
        <th class="px-4 py-3">Producto</th>
        <th class="px-4 py-3">Tipo</th>
        <th class="px-4 py-3 text-right">Cantidad</th>
        <th class="px-4 py-3">Referencia</th>
        <th class="px-4 py-3">Motivo</th>
        <th class="px-4 py-3">Usuario</th>
        <th class="px-4 py-3"></th>
    </x-slot>

    @forelse($movements as $movement)
        @php
            $motivo = $motivoLabel($movement);
            $ref    = $refLabel($movement);
            $rowData = json_encode([
                'id'          => $movement->id,
                'fecha'       => $movement->created_at->format('d/m/Y H:i:s'),
                'producto'    => $movement->product->name ?? 'N/A',
                'producto_id' => $movement->product_id,
                'almacen'     => $movement->warehouse->name ?? 'N/A',
                'usuario'     => $movement->user->name ?? 'Sistema',
                'tipo'        => $movement->type,
                'tipo_label'  => $typeLabel($movement->type),
                'tipo_icon'   => $typeIcon($movement->type),
                'cantidad'    => $movement->quantity,
                'ref'         => $ref,
                'motivo'      => $motivo,
            ], JSON_HEX_QUOT | JSON_HEX_APOS);
        @endphp
        <tr class="hover:bg-gray-50 cursor-pointer transition-colors"
            data-movement='{{ $rowData }}'>

            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                {{ $movement->created_at->format('d/m/Y') }}<br>
                <span class="text-gray-400">{{ $movement->created_at->format('H:i') }}</span>
            </td>
            <td class="px-4 py-3 font-medium text-gray-900 max-w-[180px] truncate">
                {{ $movement->product->name ?? '—' }}
            </td>
            <td class="px-4 py-3">
                @if($movement->type === 'in')
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                        📥 Entrada
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">
                        📤 Salida
                    </span>
                @endif
            </td>
            <td class="px-4 py-3 text-right font-semibold text-gray-900">
                {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
            </td>
            <td class="px-4 py-3 text-xs text-gray-500">
                {{ $ref }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 max-w-[200px] truncate" title="{{ $motivo }}">
                {{ $motivo }}
            </td>
            <td class="px-4 py-3 text-xs text-gray-400">
                {{ $movement->user->name ?? 'Sistema' }}
            </td>
            <td class="px-4 py-3 text-right">
                <button type="button"
                        onclick="openDetail(this.closest('tr'))"
                        class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Ver
                </button>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-400">
                No hay movimientos registrados.
            </td>
        </tr>
    @endforelse

    <x-slot name="footer">
        {{ $movements->links() }}
    </x-slot>

</x-table>

{{-- ── PANEL DE DETALLE ────────────────────────────────────────── --}}
<div id="detail-backdrop"
     class="fixed inset-0 bg-black/30 z-40 hidden"
     onclick="closeDetail()"></div>

<aside id="detail-panel"
       class="fixed top-0 right-0 h-full w-full max-w-sm bg-white shadow-xl z-50 flex flex-col translate-x-full transition-transform duration-300">

    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
        <div class="flex items-center gap-2">
            <span id="dp-icon" class="text-xl"></span>
            <div>
                <p id="dp-tipo" class="text-sm font-semibold text-gray-900"></p>
                <p id="dp-fecha" class="text-xs text-gray-400"></p>
            </div>
        </div>
        <button onclick="closeDetail()"
                class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>

    {{-- Cantidad destacada --}}
    <div id="dp-cantidad-box"
         class="mx-5 mt-5 rounded-xl p-4 text-center">
        <p class="text-xs font-semibold uppercase tracking-wider mb-1 opacity-70">Cantidad</p>
        <p id="dp-cantidad" class="text-4xl font-bold"></p>
    </div>

    {{-- Campos --}}
    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">

        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-1">Producto</p>
            <p id="dp-producto" class="text-sm font-medium text-gray-900"></p>
        </div>

        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-1">Almacén</p>
            <p id="dp-almacen" class="text-sm text-gray-700"></p>
        </div>

        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-1">Usuario responsable</p>
            <p id="dp-usuario" class="text-sm text-gray-700"></p>
        </div>

        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-1">Referencia</p>
            <p id="dp-ref" class="text-sm text-gray-700 font-mono"></p>
        </div>

        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-1">Motivo</p>
            <p id="dp-motivo" class="text-sm text-gray-700 leading-snug"></p>
        </div>

    </div>

    {{-- Footer --}}
    <div class="px-5 py-4 border-t border-gray-100">
        <a id="dp-link-producto" href="#"
           class="block w-full text-center rounded-lg border border-gray-300 bg-white py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            Ver ficha del producto →
        </a>
    </div>

</aside>

@endsection

@section('scripts')
<script>
function openDetail(row) {
    const data = JSON.parse(row.dataset.movement);
    const isIn = data.tipo === 'in';

    document.getElementById('dp-icon').textContent    = data.tipo_icon;
    document.getElementById('dp-tipo').textContent    = data.tipo_label + ' de inventario';
    document.getElementById('dp-fecha').textContent   = data.fecha;
    document.getElementById('dp-producto').textContent = data.producto;
    document.getElementById('dp-almacen').textContent  = data.almacen;
    document.getElementById('dp-usuario').textContent  = data.usuario;
    document.getElementById('dp-ref').textContent      = data.ref;
    document.getElementById('dp-motivo').textContent   = data.motivo;

    const cantidadEl = document.getElementById('dp-cantidad');
    cantidadEl.textContent = (isIn ? '+' : '-') + data.cantidad;

    const box = document.getElementById('dp-cantidad-box');
    box.className = 'mx-5 mt-5 rounded-xl p-4 text-center ' +
        (isIn ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700');

    const productoUrl = '/products/' + data.producto_id;
    document.getElementById('dp-link-producto').href = productoUrl;

    document.getElementById('detail-backdrop').classList.remove('hidden');
    document.getElementById('detail-panel').classList.remove('translate-x-full');
}

function closeDetail() {
    document.getElementById('detail-backdrop').classList.add('hidden');
    document.getElementById('detail-panel').classList.add('translate-x-full');
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeDetail();
});
</script>
@endsection
