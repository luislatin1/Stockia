@extends('layouts.app')

@section('title', 'Editar Producto')

@section('content')
<form method="POST"
      action="{{ route('products.update', $product) }}"
      class="bg-white p-6 rounded shadow max-w-xl">

    @csrf
    @method('PUT')

    <div class="mb-4">
        <label>Nombre</label>
        <input type="text"
               name="name"
               value="{{ old('name', $product->name) }}"
               class="w-full border p-2 rounded">
    </div>

    <div class="mb-4">
        <label>SKU</label>
        <input type="text"
               id="field-sku"
               name="sku"
               value="{{ old('sku', $product->sku) }}"
               class="w-full border p-2 rounded"
               data-unique-field="sku">
        @error('sku')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
        <p id="field-sku-unique-msg" class="text-red-500 text-sm mt-1 hidden"></p>
    </div>

    <div class="mb-4">
        <label class="inline-flex items-center gap-1">
            Código DTE
            <span class="relative group cursor-help">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 hover:text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <div class="absolute left-6 top-0 z-50 hidden group-hover:block w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg text-sm text-gray-700 font-normal leading-snug">
                    <p class="font-semibold text-gray-900 mb-1">Código DTE (Documento Tributario Electrónico)</p>
                    <p>Código interno del producto usado para la generación del DTE ante el Ministerio de Hacienda de El Salvador.</p>
                    <p class="mt-1 text-gray-500">Ejemplo: <code class="bg-gray-100 px-1 rounded">PROD-001</code>. Si no aplica, puede dejarse vacío.</p>
                </div>
            </span>
        </label>
        <input type="text"
               id="field-codigo"
               name="codigo"
               value="{{ old('codigo', $product->codigo) }}"
               class="w-full border p-2 rounded"
               data-unique-field="codigo">
        @error('codigo')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
        <p id="field-codigo-unique-msg" class="text-red-500 text-sm mt-1 hidden"></p>
    </div>

    <div class="mb-4">
        <label>Código de barras</label>
        <input type="text"
               id="field-barcode"
               name="barcode"
               value="{{ old('barcode', $product->barcode) }}"
               class="w-full border p-2 rounded"
               data-unique-field="barcode">
        @error('barcode')
            <p class="text-red-500 text-sm">{{ $message }}</p>
        @enderror
        <p id="field-barcode-unique-msg" class="text-red-500 text-sm mt-1 hidden"></p>
    </div>

    <label class="block text-sm mb-2">Stock mínimo</label>
    <input type="number"
           name="min_stock"
           value="{{ old('min_stock', $product->min_stock ?? 0) }}"
           class="border p-2 w-full mb-4">

    <div class="mb-4">
        <label>Precio</label>
        <input type="number"
               step="0.01"
               name="price"
               value="{{ old('price', $product->price) }}"
               class="w-full border p-2 rounded">
    </div>

    <div class="mb-4">
        <label>Tipo item</label>
        <select name="tipo_item" class="w-full border p-2 rounded">
            <option value="1" @selected((string) old('tipo_item', $product->tipo_item ?? 1) === '1')>Bien</option>
            <option value="2" @selected((string) old('tipo_item', $product->tipo_item ?? 1) === '2')>Servicio</option>
        </select>
    </div>

    <div class="mb-4">
        <label>Unidad de medida (CAT-014)</label>
        @php $uniOld = (string) old('uni_medida', $product->uni_medida ?? '59'); @endphp
        <select name="uni_medida" class="w-full border p-2 rounded">
            <option value="59"  @selected($uniOld === '59')>Unidad</option>
            <option value="58"  @selected($uniOld === '58')>Kilogramo</option>
            <option value="54"  @selected($uniOld === '54')>Litro</option>
            <option value="36"  @selected($uniOld === '36')>Metro</option>
            <option value="35"  @selected($uniOld === '35')>Metro cuadrado</option>
            <option value="33"  @selected($uniOld === '33')>Caja</option>
            <option value="34"  @selected($uniOld === '34')>Docena</option>
            <option value="99"  @selected($uniOld === '99')>Servicio</option>
        </select>
    </div>

    <div class="mb-4">
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="afecto_iva" value="1" {{ old('afecto_iva', $product->afecto_iva ?? true) ? 'checked' : '' }}>
            <span>Afecto IVA 13%</span>
        </label>
    </div>

    <button id="submit-btn" type="submit"
            class="bg-blue-600 text-white px-4 py-2 rounded">
        Actualizar
    </button>

</form>

@endsection

@section('scripts')
<script>
(function () {
    const CHECK_URL = '{{ route('products.check-unique') }}';
    const PRODUCT_ID = {{ $product->id }};
    const submitBtn = document.getElementById('submit-btn');
    const invalid = new Set();

    document.querySelectorAll('[data-unique-field]').forEach(input => {
        const field = input.dataset.uniqueField;
        const msg = document.getElementById('field-' + field + '-unique-msg');

        input.addEventListener('blur', async function () {
            const value = this.value.trim();
            if (!value) {
                clearError(input, msg, field);
                return;
            }

            try {
                const url = new URL(CHECK_URL, location.origin);
                url.searchParams.set('field', field);
                url.searchParams.set('value', value);
                url.searchParams.set('product_id', PRODUCT_ID);

                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();

                if (data.available) {
                    clearError(input, msg, field);
                } else {
                    setError(input, msg, field, data.message);
                }
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
