@extends('layouts.app')

@section('title', 'Ajuste Administrativo de Venta')

@section('content')
@php
    $isAdminRole = in_array(function_exists('currentRole') ? currentRole() : null, ['Admin', 'SuperAdmin'], true);
@endphp
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Ajuste Administrativo</h2>
        <p class="text-sm text-gray-500">Venta #{{ $sale->id }} | {{ $sale->created_at->format('d/m/Y H:i') }}</p>
    </div>

    @if ($errors->any())
        <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded border border-gray-200 bg-white p-4 text-sm">
        <p><span class="text-gray-500">Documento:</span> <strong>{{ strtoupper($sale->document_type ?? 'ticket') }}</strong></p>
        <p><span class="text-gray-500">Estado actual:</span> <strong>{{ ucfirst($sale->status) }}</strong></p>
        <p><span class="text-gray-500">Total venta:</span> <strong>${{ number_format((float) $sale->total, 2) }}</strong></p>
    </div>

    <form method="POST" action="{{ route('sales.admin-adjustment.store', $sale) }}" class="space-y-4">
        @csrf

        <div class="rounded border border-gray-200 bg-white p-4 space-y-3">
            <h3 class="font-semibold text-gray-900">Tipo de accion</h3>
            <label class="flex items-center gap-2">
                <input type="radio" name="action_type" value="cancel_sale" @checked(old('action_type', 'cancel_sale') === 'cancel_sale')>
                <span>Cancelar venta</span>
            </label>
            <label class="flex items-center gap-2">
                <input type="radio" name="action_type" value="void_invoice" @checked(old('action_type') === 'void_invoice')>
                <span>Anular factura</span>
            </label>
            <p class="text-xs text-gray-500">La anulacion aplica solo para comprobantes tipo factura.</p>
        </div>

        <div class="rounded border border-gray-200 bg-white p-4 space-y-3">
            <h3 class="font-semibold text-gray-900">Devolucion de efectivo</h3>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="refund_cash" value="1" @checked(old('refund_cash'))>
                <span>Registrar devolucion de efectivo</span>
            </label>
            <div>
                <label class="block text-sm text-gray-700">Monto devuelto</label>
                <input type="number" step="0.01" min="0" max="{{ (float) $sale->total }}" name="refund_amount" value="{{ old('refund_amount') }}" class="w-full rounded border border-gray-300 px-3 py-2">
            </div>
        </div>

        <div class="rounded border border-gray-200 bg-white p-4 space-y-3">
            <h3 class="font-semibold text-gray-900">Devolucion de productos</h3>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="return_products" value="1" @checked(old('return_products'))>
                <span>Registrar devolucion de productos</span>
            </label>
            <p class="text-xs text-gray-500">Estado del producto: sin abrir vuelve a inventario, dañado/caducado pasa a no vendible.</p>

            <div class="overflow-hidden rounded border">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Producto</th>
                            <th class="px-3 py-2 text-right">Vendida</th>
                            <th class="px-3 py-2 text-right">Devolver</th>
                            <th class="px-3 py-2 text-left">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($sale->items as $index => $item)
                            <tr>
                                <td class="px-3 py-2">
                                    {{ $item->product->name ?? ('#' . $item->product_id) }}
                                    <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}">
                                </td>
                                <td class="px-3 py-2 text-right">{{ (int) $item->quantity }}</td>
                                <td class="px-3 py-2">
                                    <input type="number" min="0" max="{{ (int) $item->quantity }}" name="items[{{ $index }}][quantity]" value="{{ old('items.' . $index . '.quantity', 0) }}" class="w-24 rounded border border-gray-300 px-2 py-1 text-right">
                                </td>
                                <td class="px-3 py-2">
                                    <select name="items[{{ $index }}][condition]" class="rounded border border-gray-300 px-2 py-1">
                                        <option value="">Seleccionar</option>
                                        <option value="unopened" @selected(old('items.' . $index . '.condition') === 'unopened')>Sin abrir</option>
                                        <option value="damaged" @selected(old('items.' . $index . '.condition') === 'damaged')>Dañado</option>
                                        <option value="expired" @selected(old('items.' . $index . '.condition') === 'expired')>Vencido/Caducado</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded border border-gray-200 bg-white p-4">
            <label class="block text-sm font-medium text-gray-700">Notas administrativas</label>
            <textarea name="notes" rows="3" maxlength="500" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">{{ old('notes') }}</textarea>
        </div>

        @if(! $isAdminRole)
            <div class="rounded border border-amber-200 bg-amber-50 p-4">
                <label class="block text-sm font-medium text-amber-900">Clave de administrador</label>
                <input type="password" name="admin_password" autocomplete="off" class="mt-1 w-full rounded border border-amber-300 bg-white px-3 py-2">
                <p class="mt-1 text-xs text-amber-800">Para vendedores, esta clave es obligatoria para confirmar la cancelacion/anulacion.</p>
            </div>
        @endif

        <div class="flex items-center gap-2">
            <a href="{{ route('sales.show', $sale) }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Volver</a>
            <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white">Aplicar Ajuste</button>
        </div>
    </form>
</div>
@endsection
