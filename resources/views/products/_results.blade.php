@php
    $sortLink = function ($field, $label) use ($sort, $dir) {
        $nextDir = ($sort === $field && $dir === 'asc') ? 'desc' : 'asc';
        $params = array_merge(request()->except(['sort', 'dir', 'page']), ['sort' => $field, 'dir' => $nextDir]);
        $arrow = $sort === $field ? ($dir === 'asc' ? '▲' : '▼') : '';

        return '<a href="' . route('products.index', $params) . '" class="inline-flex items-center gap-1 hover:text-gray-900">' . $label . ' <span class="text-indigo-500">' . $arrow . '</span></a>';
    };
@endphp

<x-table>

    <x-slot name="thead">
        <th class="px-4 py-3">{!! $sortLink('name', 'Nombre') !!}</th>
        <th class="px-4 py-3">SKU</th>
        <th class="px-4 py-3">Código Barras</th>
        <th class="px-4 py-3 text-right">{!! $sortLink('price', 'Precio') !!}</th>
        <th class="px-4 py-3 text-right">{!! $sortLink('stock', 'Stock') !!}</th>
        <th class="px-4 py-3 text-right">Acciones</th>
    </x-slot>

    @forelse($products as $product)
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-medium text-gray-900">{{ $product->name }}</td>
            <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $product->sku ?: '—' }}</td>
            <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $product->barcode ?: '—' }}</td>
            <td class="px-4 py-3 text-right font-medium text-gray-900">${{ number_format($product->price, 2) }}</td>
            <td class="px-4 py-3 text-right">
                @if($product->warehouse_stock <= 0)
                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                        🚫 {{ $product->warehouse_stock < 0 ? $product->warehouse_stock : 'Agotado' }}
                    </span>
                @elseif($product->warehouse_stock <= $product->warehouse_min_stock)
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                        ⚠ {{ $product->warehouse_stock }}
                    </span>
                @else
                    <span class="font-medium text-gray-900">{{ $product->warehouse_stock }}</span>
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

    <x-slot name="footer">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <span class="text-xs text-gray-400">
                {{ $products->total() }} producto(s)
                @if($search) · búsqueda: "<strong>{{ $search }}</strong>" @endif
                @if($availability === 'disponible') · filtro: disponible @endif
                @if($availability === 'low_stock') · filtro: stock bajo @endif
                @if($availability === 'agotado') · filtro: agotado @endif
            </span>
            <div>{{ $products->links() }}</div>
        </div>
    </x-slot>

</x-table>
