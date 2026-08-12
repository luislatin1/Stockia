@extends('layouts.app')

@section('title', 'Nuevo Traslado')

@section('content')

<div class="max-w-4xl">

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">

        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Nuevo traslado</h2>
            <p class="text-xs text-gray-400 mt-0.5">El stock se descuenta de la bodega actual de inmediato; la bodega destino debe confirmar la recepción para que el stock le entre.</p>
        </div>

        @if ($errors->any())
            <div class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('stock-transfers.store') }}" method="POST" class="p-6 space-y-5">
            @csrf

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bodega destino <span class="text-red-500">*</span></label>
                    <select name="destination_warehouse_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        @forelse ($destinations as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) old('destination_warehouse_id') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                        @empty
                            <option value="">No hay otra bodega disponible</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                    <input type="date" name="fecha"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                           value="{{ old('fecha', now()->format('Y-m-d')) }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                    <input type="text" name="notes"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                           value="{{ old('notes') }}">
                </div>
            </div>

            <hr class="border-gray-100">

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Productos a trasladar</label>
                    <button type="button" id="add-row"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        ＋ Agregar línea
                    </button>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-3 py-2">Producto</th>
                                <th class="px-3 py-2 w-32">Cantidad</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                    Enviar traslado
                </button>
                <a href="{{ route('stock-transfers.index') }}"
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
                <select name="items[${index}][product_id]" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                    <option value="">Selecciona un producto…</option>
                    ${productOptions('')}
                </select>
            </td>
            <td class="px-3 py-2">
                <input type="number" name="items[${index}][quantity]" min="1" value="1" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
            </td>
            <td class="px-3 py-2 text-center">
                <button type="button" class="remove-row text-gray-400 hover:text-red-600">✕</button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    document.getElementById('add-row').addEventListener('click', addRow);

    tbody.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
        }
    });

    addRow();
})();
</script>
@endsection
