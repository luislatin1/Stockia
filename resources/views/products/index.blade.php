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

{{-- ── BARRA DE BÚSQUEDA Y FILTROS (fija, no se reemplaza por AJAX) ── --}}
<div class="flex items-center gap-2 flex-wrap justify-between mb-4">
    <div class="flex items-center gap-2 flex-1 min-w-0">
        <div class="relative w-64">
            <input type="text"
                   id="product-search"
                   value="{{ $search ?? '' }}"
                   class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-full focus:outline-none focus:ring-2 focus:ring-indigo-300"
                   placeholder="Buscar por nombre, SKU o código… (mín. 3 letras)">
            <span id="product-search-spinner" class="hidden absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400">…</span>
        </div>
        <button type="button" id="product-search-clear"
                class="text-sm text-gray-400 hover:text-gray-600 {{ ($search ?? '') === '' ? 'hidden' : '' }}">
            ✕ Limpiar
        </button>
    </div>

    <div class="flex items-center gap-2 shrink-0">
        <select id="product-availability-filter"
                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            <option value="" @selected(($availability ?? '') === '')>Todos los estados</option>
            <option value="disponible" @selected(($availability ?? '') === 'disponible')>✅ Disponible</option>
            <option value="low_stock" @selected(($availability ?? '') === 'low_stock')>⚠️ Stock bajo</option>
            <option value="agotado" @selected(($availability ?? '') === 'agotado')>🚫 Agotado</option>
        </select>
    </div>
</div>

{{-- ── TABLA (esta región se reemplaza por AJAX en cada búsqueda/filtro) ── --}}
<div id="products-table-region">
    @include('products._results', compact('products', 'search', 'availability', 'sort', 'dir'))
</div>

@endsection

@section('scripts')
<script>
(function () {
    const BASE_URL = '{{ route('products.index') }}';
    const region = document.getElementById('products-table-region');
    const searchInput = document.getElementById('product-search');
    const clearBtn = document.getElementById('product-search-clear');
    const availabilitySelect = document.getElementById('product-availability-filter');
    const spinner = document.getElementById('product-search-spinner');

    let debounceTimer = null;
    const MIN_CHARS = 3;

    function buildUrl(overrides) {
        const params = new URLSearchParams(window.location.search);
        Object.entries(overrides).forEach(([key, value]) => {
            if (value === null || value === '') {
                params.delete(key);
            } else {
                params.set(key, value);
            }
        });
        params.set('page', overrides.page || 1);

        return BASE_URL + '?' + params.toString();
    }

    async function loadResults(url) {
        spinner.classList.remove('hidden');

        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;

            region.innerHTML = await res.text();
            history.replaceState(null, '', url);
            bindRegionLinks();
        } catch (_) {
            // silencioso: si falla el fetch, el usuario puede recargar la página
        } finally {
            spinner.classList.add('hidden');
        }
    }

    function bindRegionLinks() {
        // Solo interceptamos los enlaces de orden (thead) y paginación (nav);
        // los de Editar/Ajustar/Ver dentro de cada fila deben navegar normalmente.
        region.querySelectorAll('thead a[href], nav[role="navigation"] a[href]').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                loadResults(this.href);
            });
        });
    }

    searchInput.addEventListener('input', function () {
        const value = this.value.trim();
        clearBtn.classList.toggle('hidden', value === '');

        if (value.length > 0 && value.length < MIN_CHARS) return;

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            loadResults(buildUrl({ q: value, page: 1 }));
        }, 300);
    });

    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        clearBtn.classList.add('hidden');
        loadResults(buildUrl({ q: null, page: 1 }));
    });

    availabilitySelect.addEventListener('change', function () {
        loadResults(buildUrl({ availability: this.value || null, page: 1 }));
    });

    bindRegionLinks();
})();
</script>
@endsection
