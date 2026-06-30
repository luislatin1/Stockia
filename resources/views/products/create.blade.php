@extends('layouts.app')

@section('title', 'Nuevo Producto')

@section('content')

<div class="max-w-2xl">

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">

        {{-- Card header --}}
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Datos del producto</h2>
            <p class="text-xs text-gray-400 mt-0.5">Completa los campos obligatorios para registrar el producto.</p>
        </div>

        <form action="{{ route('products.store') }}" method="POST" class="p-6 space-y-5">
            @csrf

            {{-- Nombre --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="name"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                       value="{{ old('name') }}" placeholder="Nombre del producto">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- SKU + Código Barras (2 columnas) --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input type="text" id="field-sku" name="sku"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                           value="{{ old('sku') }}" placeholder="SKU interno"
                           data-unique-field="sku">
                    @error('sku')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p id="field-sku-unique-msg" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código de barras</label>
                    <input type="text" id="field-barcode" name="barcode"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                           value="{{ old('barcode') }}" placeholder="Escanea o escribe"
                           data-unique-field="barcode">
                    @error('barcode')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <p id="field-barcode-unique-msg" class="text-red-500 text-xs mt-1 hidden"></p>
                </div>
            </div>

            {{-- Código DTE --}}
            <div>
                <label class="inline-flex items-center gap-1 text-sm font-medium text-gray-700 mb-1">
                    Código DTE
                    <span class="relative group cursor-help">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 hover:text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        <div class="absolute left-6 top-0 z-50 hidden group-hover:block w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg text-xs text-gray-700 leading-snug">
                            <p class="font-semibold text-gray-900 mb-1">Código DTE (Documento Tributario Electrónico)</p>
                            <p>Código interno del producto para la generación del DTE ante Hacienda El Salvador. Si no aplica, déjalo vacío.</p>
                        </div>
                    </span>
                </label>
                <input type="text" id="field-codigo" name="codigo"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                       value="{{ old('codigo') }}" placeholder="Ej. PROD-001"
                       data-unique-field="codigo">
                @error('codigo')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p id="field-codigo-unique-msg" class="text-red-500 text-xs mt-1 hidden"></p>
            </div>

            <hr class="border-gray-100">

            {{-- Precio + Stock (2 columnas) --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-sm text-gray-400">$</span>
                        <input type="number" step="0.01" name="price"
                               class="w-full rounded-lg border border-gray-300 pl-7 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                               value="{{ old('price') }}" placeholder="0.00">
                    </div>
                    @error('price')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock inicial</label>
                    <input type="number" name="stock"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                           value="{{ old('stock', 0) }}" min="0">
                    @error('stock')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Stock mínimo --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stock mínimo</label>
                <input type="number" name="min_stock"
                       value="{{ old('min_stock', 0) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                       min="0">
            </div>

            <hr class="border-gray-100">

            {{-- Tipo item + Unidad de medida (2 columnas) --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de ítem</label>
                    <select name="tipo_item" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="1" @selected((string) old('tipo_item', '1') === '1')>Bien</option>
                        <option value="2" @selected((string) old('tipo_item') === '2')>Servicio</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unidad de medida (CAT-014)</label>
                    @php $uniOld = (string) old('uni_medida', '59'); @endphp
                    <select name="uni_medida" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="59" @selected($uniOld === '59')>Unidad</option>
                        <option value="58" @selected($uniOld === '58')>Kilogramo</option>
                        <option value="54" @selected($uniOld === '54')>Litro</option>
                        <option value="36" @selected($uniOld === '36')>Metro</option>
                        <option value="35" @selected($uniOld === '35')>Metro cuadrado</option>
                        <option value="33" @selected($uniOld === '33')>Caja</option>
                        <option value="34" @selected($uniOld === '34')>Docena</option>
                        <option value="99" @selected($uniOld === '99')>Servicio</option>
                    </select>
                </div>
            </div>

            {{-- Afecto IVA --}}
            <div>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="afecto_iva" value="1"
                           class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           {{ old('afecto_iva', '1') ? 'checked' : '' }}>
                    <span class="text-sm font-medium text-gray-700">Afecto IVA 13%</span>
                </label>
            </div>

            {{-- Botones --}}
            <div class="flex items-center gap-3 pt-2">
                <button id="submit-btn"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                    Guardar producto
                </button>
                <a href="{{ route('products.index') }}"
                   class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
            </div>

        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
(function () {
    const CHECK_URL = '{{ route('products.check-unique') }}';
    const PRODUCT_ID = null;
    const submitBtn = document.getElementById('submit-btn');
    const invalid = new Set();

    document.querySelectorAll('[data-unique-field]').forEach(input => {
        const field = input.dataset.uniqueField;
        const msg = document.getElementById('field-' + field + '-unique-msg');

        input.addEventListener('blur', async function () {
            const value = this.value.trim();
            if (!value) { clearError(input, msg, field); return; }

            try {
                const url = new URL(CHECK_URL, location.origin);
                url.searchParams.set('field', field);
                url.searchParams.set('value', value);
                if (PRODUCT_ID) url.searchParams.set('product_id', PRODUCT_ID);

                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                data.available ? clearError(input, msg, field) : setError(input, msg, field, data.message);
            } catch (_) {}
        });

        input.addEventListener('input', function () {
            if (invalid.has(field)) clearError(input, msg, field);
        });
    });

    function setError(input, msg, field, text) {
        input.classList.add('border-red-500');
        msg.textContent = text;
        msg.classList.remove('hidden');
        invalid.add(field);
        if (submitBtn) submitBtn.disabled = true;
    }

    function clearError(input, msg, field) {
        input.classList.remove('border-red-500');
        msg.textContent = '';
        msg.classList.add('hidden');
        invalid.delete(field);
        if (submitBtn) submitBtn.disabled = invalid.size > 0;
    }
})();
</script>
@endsection
