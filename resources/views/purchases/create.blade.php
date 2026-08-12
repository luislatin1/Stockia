@extends('layouts.app')

@section('title', 'Nueva Compra')

@section('content')

<div class="max-w-4xl">

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">

        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Datos de la compra</h2>
            <p class="text-xs text-gray-400 mt-0.5">Registra los productos recibidos, su costo, y el stock se actualizará automáticamente.</p>
        </div>

        <form action="{{ route('purchases.store') }}" method="POST" class="p-6 space-y-5">
            @csrf

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                    <select name="provider_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">Sin proveedor</option>
                        @foreach ($providers as $provider)
                            <option value="{{ $provider->id }}" @selected((string) old('provider_id') === (string) $provider->id)>{{ $provider->nombre }}</option>
                        @endforeach
                    </select>
                    @error('provider_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                           value="{{ old('fecha', now()->format('Y-m-d')) }}">
                    @error('fecha')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">No. Documento</label>
                    <input type="text" name="numero_doc"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                           value="{{ old('numero_doc') }}" placeholder="Factura del proveedor">
                </div>
            </div>

            <hr class="border-gray-100">

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Líneas de la compra</label>
                    <button type="button" id="add-row"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        ＋ Agregar línea
                    </button>
                </div>

                @error('items')
                    <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
                @enderror

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-3 py-2">Producto</th>
                                <th class="px-3 py-2 w-24">Cantidad</th>
                                <th class="px-3 py-2 w-32">Costo unitario</th>
                                <th class="px-3 py-2 w-32 text-right">Subtotal</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end">
                <div class="w-48 text-right">
                    <span class="text-sm text-gray-500">Total</span>
                    <p id="grand-total" class="text-xl font-bold text-gray-900">$0.00</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                <input type="text" name="notes"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                       value="{{ old('notes') }}">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                    Guardar compra
                </button>
                <a href="{{ route('purchases.index') }}"
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
    const products = @json($products->map(fn ($p) => ['id' => $p->id, 'label' => $p->name . ($p->sku ? ' (' . $p->sku . ')' : '')]));
    const tbody = document.getElementById('items-body');
    const grandTotalEl = document.getElementById('grand-total');
    let rowIndex = 0;

    function productOptions(selectedId) {
        return products.map(p =>
            `<option value="${p.id}" ${String(p.id) === String(selectedId) ? 'selected' : ''}>${p.label}</option>`
        ).join('');
    }

    function addRow() {
        const index = rowIndex++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-3 py-2">
                <select name="items[${index}][product_id]" class="row-product w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                    <option value="">Selecciona un producto…</option>
                    ${productOptions('')}
                </select>
            </td>
            <td class="px-3 py-2">
                <input type="number" name="items[${index}][cantidad]" min="1" value="1" class="row-cantidad w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
            </td>
            <td class="px-3 py-2">
                <input type="number" step="0.01" min="0" name="items[${index}][costo_unitario]" value="0.00" class="row-costo w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
            </td>
            <td class="px-3 py-2 text-right row-subtotal font-medium text-gray-900">$0.00</td>
            <td class="px-3 py-2 text-center">
                <button type="button" class="remove-row text-gray-400 hover:text-red-600">✕</button>
            </td>
        `;
        tbody.appendChild(tr);
        recalculate();
    }

    function recalculate() {
        let grandTotal = 0;
        tbody.querySelectorAll('tr').forEach(tr => {
            const cantidad = parseFloat(tr.querySelector('.row-cantidad').value) || 0;
            const costo = parseFloat(tr.querySelector('.row-costo').value) || 0;
            const subtotal = cantidad * costo;
            tr.querySelector('.row-subtotal').textContent = '$' + subtotal.toFixed(2);
            grandTotal += subtotal;
        });
        grandTotalEl.textContent = '$' + grandTotal.toFixed(2);
    }

    document.getElementById('add-row').addEventListener('click', addRow);

    tbody.addEventListener('input', function (e) {
        if (e.target.classList.contains('row-cantidad') || e.target.classList.contains('row-costo')) {
            recalculate();
        }
    });

    tbody.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
            recalculate();
        }
    });

    addRow();
})();
</script>
@endsection
