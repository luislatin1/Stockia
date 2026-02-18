@extends('layouts.app')

@section('title', 'Nueva Venta')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Nueva Venta</h2>
        <p class="text-sm text-gray-500">Precios base sin impuesto. Se aplica IVA 13% al total.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('sales.store') }}" method="POST" class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        @csrf

        <div class="xl:col-span-2">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Producto</th>
                            <th class="px-4 py-3 text-left">Precio Base</th>
                            <th class="px-4 py-3 text-left">Stock</th>
                            <th class="px-4 py-3 text-left">Cantidad</th>
                            <th class="px-4 py-3 text-left">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($products as $product)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $product->name }}</td>
                                <td class="px-4 py-3">${{ number_format($product->price, 2) }}</td>
                                <td class="px-4 py-3">{{ $product->stock }}</td>
                                <td class="px-4 py-3">
                                    <input type="number"
                                           min="0"
                                           max="{{ $product->stock }}"
                                           name="products[{{ $product->id }}]"
                                           value="{{ old('products.' . $product->id, 0) }}"
                                           data-price="{{ $product->price }}"
                                           class="product-qty w-24 rounded border border-gray-300 px-2 py-1">
                                </td>
                                <td class="px-4 py-3">
                                    $<span class="line-subtotal">0.00</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay productos disponibles en este almacén.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="xl:col-span-1">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Resumen de Pago</h3>

                <div class="space-y-1 text-sm">
                    <div class="flex items-center justify-between">
                        <span>Subtotal</span>
                        <span>$<span id="subtotal">0.00</span></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>IVA 13%</span>
                        <span>$<span id="tax">0.00</span></span>
                    </div>
                    <div class="flex items-center justify-between border-t pt-2 font-semibold text-base">
                        <span>Total</span>
                        <span>$<span id="total">0.00</span></span>
                    </div>
                </div>

                <div>
                    <label for="cash_received" class="mb-1 block text-sm font-medium text-gray-700">Efectivo recibido</label>
                    <input id="cash_received"
                           type="number"
                           step="0.01"
                           min="0"
                           name="cash_received"
                           value="{{ old('cash_received') }}"
                           class="w-full rounded border border-gray-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Comprobante</label>
                    <select id="document_type" name="document_type" class="w-full rounded border border-gray-300 px-3 py-2">
                        <option value="ticket" {{ old('document_type', 'ticket') === 'ticket' ? 'selected' : '' }}>Ticket</option>
                        <option value="factura" {{ old('document_type') === 'factura' ? 'selected' : '' }}>Factura</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Tipo DTE</label>
                    <input id="tipo_dte" type="text" name="tipo_dte" value="{{ old('tipo_dte') }}" placeholder="01, 03, 05..." class="w-full rounded border border-gray-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Cliente (opcional)</label>
                    <select name="customer_id" class="w-full rounded border border-gray-300 px-3 py-2">
                        <option value="">Consumidor final</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>
                                {{ $customer->nombre }} ({{ $customer->tipo_documento }} {{ $customer->numero_documento }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="w-full rounded bg-emerald-600 px-4 py-2 font-semibold text-white hover:bg-emerald-700">
                    Confirmar Venta
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ivaRate = 0.13;
    const qtyInputs = document.querySelectorAll('.product-qty');
    const subtotalEl = document.getElementById('subtotal');
    const taxEl = document.getElementById('tax');
    const totalEl = document.getElementById('total');
    const documentTypeEl = document.getElementById('document_type');
    const tipoDteEl = document.getElementById('tipo_dte');

    function recalc() {
        let subtotal = 0;

        qtyInputs.forEach((input) => {
            const qty = parseInt(input.value || '0', 10);
            const price = parseFloat(input.dataset.price || '0');
            const row = input.closest('tr');
            const lineSubtotal = Math.max(0, qty) * price;
            subtotal += lineSubtotal;

            const lineEl = row.querySelector('.line-subtotal');
            if (lineEl) {
                lineEl.textContent = lineSubtotal.toFixed(2);
            }
        });

        const tax = subtotal * ivaRate;
        const total = subtotal + tax;

        subtotalEl.textContent = subtotal.toFixed(2);
        taxEl.textContent = tax.toFixed(2);
        totalEl.textContent = total.toFixed(2);
    }

    qtyInputs.forEach((input) => {
        input.addEventListener('input', recalc);
    });

    if (documentTypeEl && tipoDteEl && !tipoDteEl.value) {
        tipoDteEl.value = documentTypeEl.value === 'factura' ? '01' : '';
    }

    if (documentTypeEl && tipoDteEl) {
        documentTypeEl.addEventListener('change', () => {
            if (tipoDteEl.value.trim() === '' || tipoDteEl.value === '01') {
                tipoDteEl.value = documentTypeEl.value === 'factura' ? '01' : '';
            }
        });
    }

    recalc();
});
</script>
@endsection
