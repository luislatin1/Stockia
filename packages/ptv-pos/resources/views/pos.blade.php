@extends('layouts.app')

@section('title', 'PTV-POS')

@section('content')
@php
    $errorsList = $errors->all();
    $hasStockShortageError = collect($errorsList)->contains(fn ($msg) => str_contains($msg, 'Stock insuficiente en sistema'));
    $oldItems = old('items', []);
@endphp

@if ($errors->any())
<div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
    <p class="font-semibold">No se pudo completar la venta:</p>
    <ul class="mt-1 list-disc pl-5">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    @if($hasStockShortageError && !empty($oldItems))
    <button id="open-stock-adjust-modal" type="button" class="mt-3 rounded bg-amber-600 px-3 py-2 text-xs font-semibold text-white">
        Autorizar ajuste ahora
    </button>
    @endif
</div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <label class="block text-sm font-medium text-gray-700">Escaner (codigo de barras o SKU)</label>
            <div class="mt-1 flex gap-2">
                <input id="barcode-input" type="text" class="w-full rounded border border-gray-300 px-3 py-2" placeholder="Escribe SKU o codigo y presiona Enter" autofocus>
                <input id="quantity-input" type="number" min="1" step="1" value="1" class="w-24 rounded border border-gray-300 px-3 py-2 text-right" title="Cantidad">
                <button id="add-manual-btn" type="button" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Agregar</button>
            </div>
            <p id="scan-error" class="mt-2 text-sm text-red-600 hidden"></p>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full text-sm" id="cart-table">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Producto</th>
                        <th class="px-4 py-3 text-left">Codigo</th>
                        <th class="px-4 py-3 text-right">Precio</th>
                        <th class="px-4 py-3 text-right">Cantidad</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cart-body"></tbody>
            </table>
        </div>
    </div>

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm space-y-2">
            <p class="text-xs uppercase text-gray-500">Caja activa</p>
            <p class="text-sm text-gray-700">
                <span class="font-semibold">{{ $activeSession->register_name ?? 'Sin nombre' }}</span>
                ({{ $activeSession->register_code ?? 'N/A' }})
            </p>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="rounded bg-gray-50 p-2">
                    <p class="text-gray-500">Base apertura</p>
                    <p class="font-semibold text-gray-900">${{ number_format((float) $activeSession->opening_cash, 2) }}</p>
                </div>
                <div class="rounded bg-gray-50 p-2">
                    <p class="text-gray-500">Esperado actual</p>
                    <p class="font-semibold text-gray-900">${{ number_format((float) ($snapshot['expected_cash'] ?? 0), 2) }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm space-y-2">
            <div class="flex justify-between text-sm">
                <span>Subtotal</span>
                <span id="subtotal">$0.00</span>
            </div>
            <div class="flex justify-between text-sm">
                <span>IVA 13%</span>
                <span id="tax">$0.00</span>
            </div>
            <div class="flex justify-between font-semibold">
                <span>Total</span>
                <span id="total">$0.00</span>
            </div>
        </div>

        <form id="checkout-form" method="POST" action="{{ route('ptvpos.checkout') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm space-y-3">
            @csrf
            <input type="hidden" name="admin_password" id="admin-password">
            <div>
                <label class="block text-sm font-medium text-gray-700">Comprobante</label>
                <select name="document_type" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                    <option value="ticket" @selected(old('document_type', 'ticket') === 'ticket')>Ticket</option>
                    <option value="factura" @selected(old('document_type') === 'factura')>Factura</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Efectivo recibido</label>
                <input id="cash-received" type="number" step="0.01" min="0" name="cash_received" required class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
            </div>
            <button class="w-full rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Cobrar</button>
        </form>
    </div>
</div>

<div id="admin-modal" class="fixed inset-0 hidden items-center justify-center bg-black/50">
    <div class="rounded-lg bg-white p-4 w-full max-w-sm">
        <h3 class="text-lg font-semibold">Clave de administrador</h3>
        <p class="text-sm text-gray-500">Se requiere para cambiar precios.</p>
        <input type="password" id="admin-pass-input" class="mt-3 w-full rounded border border-gray-300 px-3 py-2">
        <div class="mt-3 flex justify-end gap-2">
            <button type="button" id="admin-cancel" class="rounded border px-3 py-2">Cancelar</button>
            <button type="button" id="admin-confirm" class="rounded bg-indigo-600 px-3 py-2 text-white">Validar</button>
        </div>
        <p id="admin-error" class="mt-2 text-sm text-red-600 hidden"></p>
    </div>
</div>

@if($hasStockShortageError && !empty($oldItems))
<form id="stock-adjust-form" method="POST" action="{{ route('ptvpos.checkout') }}" class="hidden">
    @csrf
    <input type="hidden" name="document_type" value="{{ old('document_type', 'ticket') }}">
    <input type="hidden" name="cash_received" value="{{ old('cash_received', 0) }}">
    <input type="hidden" name="force_stock_adjustment" value="1">
    <input type="hidden" name="stock_adjustment_reason" id="stock-adjust-reason-field">
    <input type="hidden" name="admin_password" id="stock-adjust-password-field">
    @foreach($oldItems as $i => $item)
        <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $item['product_id'] ?? '' }}">
        <input type="hidden" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? '' }}">
        <input type="hidden" name="items[{{ $i }}][price]" value="{{ $item['price'] ?? '' }}">
    @endforeach
</form>

<div id="stock-adjust-modal" class="fixed inset-0 hidden items-center justify-center bg-black/50">
    <div class="w-full max-w-md rounded-lg bg-white p-4">
        <h3 class="text-lg font-semibold">Autorizar ajuste temporal</h3>
        <p class="text-sm text-gray-500">Ingresa motivo y clave de administrador para continuar.</p>
        <div class="mt-3 space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">Motivo</label>
                <input id="stock-adjust-reason-input" type="text" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" placeholder="Ej: diferencia en conteo fisico">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Clave admin</label>
                <input id="stock-adjust-password-input" type="password" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
            </div>
            <p id="stock-adjust-error" class="hidden text-sm text-red-600"></p>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <button type="button" id="stock-adjust-cancel" class="rounded border px-3 py-2 text-sm">Cancelar</button>
            <button type="button" id="stock-adjust-confirm" class="rounded bg-amber-600 px-3 py-2 text-sm text-white">Autorizar y cobrar</button>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cart = new Map();
    const barcodeInput = document.getElementById('barcode-input');
    const quantityInput = document.getElementById('quantity-input');
    const addManualBtn = document.getElementById('add-manual-btn');
    const cartBody = document.getElementById('cart-body');
    const scanError = document.getElementById('scan-error');
    const adminModal = document.getElementById('admin-modal');
    const adminPassInput = document.getElementById('admin-pass-input');
    const adminError = document.getElementById('admin-error');
    const adminPasswordField = document.getElementById('admin-password');
    const checkoutForm = document.getElementById('checkout-form');
    const cashReceivedInput = document.getElementById('cash-received');
    const openStockAdjustBtn = document.getElementById('open-stock-adjust-modal');
    const stockAdjustModal = document.getElementById('stock-adjust-modal');
    const stockAdjustCancel = document.getElementById('stock-adjust-cancel');
    const stockAdjustConfirm = document.getElementById('stock-adjust-confirm');
    const stockAdjustReasonInput = document.getElementById('stock-adjust-reason-input');
    const stockAdjustPasswordInput = document.getElementById('stock-adjust-password-input');
    const stockAdjustReasonField = document.getElementById('stock-adjust-reason-field');
    const stockAdjustPasswordField = document.getElementById('stock-adjust-password-field');
    const stockAdjustForm = document.getElementById('stock-adjust-form');
    const stockAdjustError = document.getElementById('stock-adjust-error');
    let pendingPriceEdit = null;

    function money(value) {
        return '$' + value.toFixed(2);
    }

    function recalcTotals() {
        let subtotal = 0;
        cart.forEach(item => {
            subtotal += item.price * item.quantity;
        });
        const tax = subtotal * 0.13;
        const total = subtotal + tax;
        document.getElementById('subtotal').textContent = money(subtotal);
        document.getElementById('tax').textContent = money(tax);
        document.getElementById('total').textContent = money(total);
    }

    function renderCart() {
        cartBody.innerHTML = '';
        cart.forEach((item, id) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-4 py-2">${item.name}</td>
                <td class="px-4 py-2">${item.barcode || item.sku || '-'}</td>
                <td class="px-4 py-2 text-right">
                    <input type="number" step="0.01" min="0" value="${item.price.toFixed(2)}" data-id="${id}" class="price-input w-24 rounded border px-2 py-1 text-right">
                </td>
                <td class="px-4 py-2 text-right">${item.quantity}</td>
                <td class="px-4 py-2 text-right">${money(item.price * item.quantity)}</td>
                <td class="px-4 py-2 text-right">
                    <button type="button" data-id="${id}" class="remove-item text-red-600">Quitar</button>
                </td>
            `;
            cartBody.appendChild(tr);
        });
        recalcTotals();
    }

    function resolveQty() {
        const rawQty = parseInt(quantityInput.value, 10);
        return Number.isNaN(rawQty) || rawQty < 1 ? 1 : rawQty;
    }

    async function scanBarcode(code, qty) {
        scanError.classList.add('hidden');
        const res = await fetch("{{ route('ptvpos.scan') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ barcode: code })
        });

        if (!res.ok) {
            const data = await res.json();
            scanError.textContent = data.message || 'Error al buscar producto.';
            scanError.classList.remove('hidden');
            return;
        }

        const product = await res.json();
        const current = cart.get(product.id);
        if (current) {
            current.quantity += qty;
        } else {
            cart.set(product.id, { ...product, quantity: qty });
        }
        renderCart();
    }

    async function addFromInput() {
        const code = barcodeInput.value.trim();
        if (!code) {
            return;
        }
        const qty = resolveQty();
        await scanBarcode(code, qty);
        barcodeInput.value = '';
        quantityInput.value = '1';
        barcodeInput.focus();
    }

    barcodeInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addFromInput();
        }
    });

    addManualBtn.addEventListener('click', () => {
        addFromInput();
    });

    cartBody.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-item')) {
            const id = parseInt(e.target.dataset.id, 10);
            cart.delete(id);
            renderCart();
        }
    });

    cartBody.addEventListener('change', (e) => {
        if (e.target.classList.contains('price-input')) {
            const id = parseInt(e.target.dataset.id, 10);
            const newPrice = parseFloat(e.target.value);

            if (!cart.has(id) || Number.isNaN(newPrice) || newPrice < 0) {
                renderCart();
                return;
            }

            pendingPriceEdit = { id, newPrice };
            adminPassInput.value = '';
            adminError.classList.add('hidden');
            adminModal.classList.remove('hidden');
        }
    });

    document.getElementById('admin-cancel').addEventListener('click', () => {
        pendingPriceEdit = null;
        adminModal.classList.add('hidden');
        renderCart();
    });

    document.getElementById('admin-confirm').addEventListener('click', async () => {
        const password = adminPassInput.value;
        const res = await fetch("{{ route('ptvpos.adminAuth') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ password })
        });

        if (!res.ok) {
            adminError.textContent = 'Clave invalida';
            adminError.classList.remove('hidden');
            return;
        }

        if (pendingPriceEdit && cart.has(pendingPriceEdit.id)) {
            const item = cart.get(pendingPriceEdit.id);
            item.price = pendingPriceEdit.newPrice;
            cart.set(pendingPriceEdit.id, item);
            renderCart();
        }

        adminPasswordField.value = password;
        pendingPriceEdit = null;
        adminModal.classList.add('hidden');
    });

    checkoutForm.addEventListener('submit', (e) => {
        if (cart.size === 0) {
            e.preventDefault();
            alert('Agrega productos al carrito.');
            return;
        }

        const cashValue = parseFloat(cashReceivedInput.value);
        if (Number.isNaN(cashValue) || cashValue < 0) {
            e.preventDefault();
            alert('Ingresa un monto valido en efectivo recibido.');
            cashReceivedInput.focus();
            return;
        }

        checkoutForm.querySelectorAll('input[data-cart-item="1"]').forEach((el) => el.remove());

        let index = 0;
        cart.forEach((item) => {
            const fields = [
                { name: `items[${index}][product_id]`, value: item.id },
                { name: `items[${index}][quantity]`, value: item.quantity },
                { name: `items[${index}][price]`, value: item.price.toFixed(2) },
            ];
            fields.forEach((field) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = field.name;
                input.value = field.value;
                input.dataset.cartItem = '1';
                checkoutForm.appendChild(input);
            });
            index += 1;
        });
    });

    if (openStockAdjustBtn && stockAdjustModal) {
        openStockAdjustBtn.addEventListener('click', () => {
            stockAdjustError.classList.add('hidden');
            stockAdjustReasonInput.value = '';
            stockAdjustPasswordInput.value = '';
            stockAdjustModal.classList.remove('hidden');
        });
    }

    if (stockAdjustCancel && stockAdjustModal) {
        stockAdjustCancel.addEventListener('click', () => {
            stockAdjustModal.classList.add('hidden');
        });
    }

    if (stockAdjustConfirm && stockAdjustModal) {
        stockAdjustConfirm.addEventListener('click', async () => {
            const reason = (stockAdjustReasonInput?.value || '').trim();
            const password = (stockAdjustPasswordInput?.value || '').trim();

            if (!reason) {
                stockAdjustError.textContent = 'Debes indicar un motivo.';
                stockAdjustError.classList.remove('hidden');
                return;
            }

            if (!password) {
                stockAdjustError.textContent = 'Debes ingresar la clave de administrador.';
                stockAdjustError.classList.remove('hidden');
                return;
            }

            const res = await fetch("{{ route('ptvpos.adminAuth') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ password })
            });

            if (!res.ok) {
                stockAdjustError.textContent = 'Clave de administrador invalida.';
                stockAdjustError.classList.remove('hidden');
                return;
            }

            stockAdjustReasonField.value = reason;
            stockAdjustPasswordField.value = password;
            stockAdjustForm.submit();
        });
    }

    barcodeInput.focus();
});
</script>
@endsection
