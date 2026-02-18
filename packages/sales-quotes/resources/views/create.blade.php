@extends('layouts.app')

@section('title', 'Nueva Cotización')

@section('content')
@php
    $oldItems = old('items', []);
    if (empty($oldItems)) {
        $oldItems = [
            [
                'product_id' => '',
                'quantity' => '',
                'price' => '',
                'discount_percent' => 0,
            ],
        ];
    }
@endphp
<div class="max-w-5xl space-y-6">
    <h2 class="text-2xl font-bold text-gray-900">Crear Cotización</h2>

    @if ($errors->any())
        <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('salesquotes.store') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">Cliente</label>
                <input type="text" name="customer_name" required value="{{ old('customer_name') }}" class="w-full rounded border border-gray-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Válida Hasta</label>
                <input type="date" name="valid_until" value="{{ old('valid_until') }}" class="w-full rounded border border-gray-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email Cliente</label>
                <input type="email" name="customer_email" value="{{ old('customer_email') }}" class="w-full rounded border border-gray-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Teléfono Cliente</label>
                <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" class="w-full rounded border border-gray-300 px-3 py-2">
            </div>
        </div>

        <div class="rounded border bg-white p-4">
            <p class="mb-2 text-sm font-semibold text-gray-800">Items</p>
            <div id="quote-items" class="space-y-3">
                @foreach($oldItems as $i => $oldItem)
                    <div class="quote-item-row grid grid-cols-1 gap-3 md:grid-cols-7" data-row="{{ $i }}">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-700">Producto</label>
                            <select name="items[{{ $i }}][product_id]" class="quote-product w-full rounded border border-gray-300 px-3 py-2" data-row="{{ $i }}">
                                <option value="">Seleccionar producto</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ number_format((float) $product->price, 2, '.', '') }}" data-stock="{{ (int) $product->stock }}" @selected((string) ($oldItem['product_id'] ?? '') === (string) $product->id)>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700">Cantidad</label>
                            <input type="number" min="1" name="items[{{ $i }}][quantity]" placeholder="Cantidad" value="{{ $oldItem['quantity'] ?? '' }}" class="w-full rounded border border-gray-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700">Precio</label>
                            <input type="number" step="0.01" min="0" name="items[{{ $i }}][price]" placeholder="Precio" value="{{ $oldItem['price'] ?? '' }}" class="quote-price w-full rounded border border-gray-300 bg-gray-50 px-3 py-2" data-row="{{ $i }}" readonly>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700">Desc. %</label>
                            <input type="number" step="0.01" min="0" max="100" name="items[{{ $i }}][discount_percent]" placeholder="Desc. %" value="{{ $oldItem['discount_percent'] ?? 0 }}" class="w-full rounded border border-gray-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-700">Stock</label>
                            <input type="text" value="" class="quote-stock w-full rounded border border-gray-300 bg-gray-50 px-3 py-2" data-row="{{ $i }}" placeholder="Stock" readonly>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-transparent select-none">Acción</label>
                            <button type="button" class="remove-item w-full rounded border border-red-300 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">Quitar</button>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3">
                <button type="button" id="add-item" class="rounded border border-indigo-300 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">
                    + Agregar item
                </button>
            </div>
            <p class="mt-2 text-xs text-gray-500">Completa solo las líneas que necesites. El precio y stock se toman del almacén actual.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Notas</label>
            <textarea name="notes" rows="3" class="w-full rounded border border-gray-300 px-3 py-2">{{ old('notes') }}</textarea>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('salesquotes.index') }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Cancelar</a>
            <button class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Guardar Cotización</button>
        </div>
    </form>
</div>
<template id="quote-item-template">
    <div class="quote-item-row grid grid-cols-1 gap-3 md:grid-cols-7" data-row="__INDEX__">
        <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-medium text-gray-700">Producto</label>
            <select name="items[__INDEX__][product_id]" class="quote-product w-full rounded border border-gray-300 px-3 py-2" data-row="__INDEX__">
                <option value="">Seleccionar producto</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ number_format((float) $product->price, 2, '.', '') }}" data-stock="{{ (int) $product->stock }}">
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">Cantidad</label>
            <input type="number" min="1" name="items[__INDEX__][quantity]" placeholder="Cantidad" class="w-full rounded border border-gray-300 px-3 py-2">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">Precio</label>
            <input type="number" step="0.01" min="0" name="items[__INDEX__][price]" placeholder="Precio" class="quote-price w-full rounded border border-gray-300 bg-gray-50 px-3 py-2" data-row="__INDEX__" readonly>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">Desc. %</label>
            <input type="number" step="0.01" min="0" max="100" name="items[__INDEX__][discount_percent]" placeholder="Desc. %" value="0" class="w-full rounded border border-gray-300 px-3 py-2">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">Stock</label>
            <input type="text" value="" class="quote-stock w-full rounded border border-gray-300 bg-gray-50 px-3 py-2" data-row="__INDEX__" placeholder="Stock" readonly>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-transparent select-none">Acción</label>
            <button type="button" class="remove-item w-full rounded border border-red-300 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700">Quitar</button>
        </div>
    </div>
</template>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const syncRow = (row) => {
        const select = document.querySelector('.quote-product[data-row="' + row + '"]');
        const priceInput = document.querySelector('.quote-price[data-row="' + row + '"]');
        const stockInput = document.querySelector('.quote-stock[data-row="' + row + '"]');
        if (!select || !priceInput || !stockInput) return;

        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            priceInput.value = '';
            stockInput.value = '';
            return;
        }

        priceInput.value = selectedOption.dataset.price || '';
        stockInput.value = selectedOption.dataset.stock || '';
    };

    const bindRow = (rowElement) => {
        const select = rowElement.querySelector('.quote-product');
        if (!select) return;
        const row = select.getAttribute('data-row');
        syncRow(row);
        select.addEventListener('change', function () {
            syncRow(row);
        });
    };

    const container = document.getElementById('quote-items');
    const addButton = document.getElementById('add-item');
    const template = document.getElementById('quote-item-template');
    let nextIndex = container.querySelectorAll('.quote-item-row').length;

    container.querySelectorAll('.quote-item-row').forEach((rowElement) => {
        bindRow(rowElement);
    });

    container.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-item');
        if (!removeButton) return;
        const rows = container.querySelectorAll('.quote-item-row');
        if (rows.length <= 1) {
            return;
        }
        removeButton.closest('.quote-item-row')?.remove();
    });

    addButton.addEventListener('click', function () {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const rowElement = wrapper.firstElementChild;
        if (!rowElement) return;
        container.appendChild(rowElement);
        bindRow(rowElement);
        nextIndex += 1;
    });
});
</script>
@endsection
