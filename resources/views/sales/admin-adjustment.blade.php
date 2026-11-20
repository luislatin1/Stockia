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
            <label class="flex items-center gap-2">
                <input type="radio" name="action_type" value="return_items" @checked(old('action_type') === 'return_items')>
                <span>Devolver / ajustar (la venta sigue vigente)</span>
            </label>
            <p class="text-xs text-gray-500">La anulacion aplica solo para comprobantes tipo factura. "Devolver / ajustar" permite devoluciones parciales y ajustes de valor.</p>
        </div>

        @if($dteInfo)
            <div class="rounded border border-indigo-200 bg-indigo-50 p-4 space-y-3">
                <h3 class="font-semibold text-indigo-900">Documento tributario electrónico</h3>
                <p class="text-sm text-indigo-900">
                    Esta venta tiene {{ $dteInfo['tipo'] === '03' ? 'un Comprobante de Crédito Fiscal' : 'una Factura' }} electrónica
                    ({{ $dteInfo['estado'] }}) <span class="text-xs">{{ $dteInfo['numero_control'] }}</span>.
                    El motivo define qué se le informa a Hacienda.
                </p>
                <div>
                    <label class="block text-sm font-medium text-indigo-900">Motivo</label>
                    <select name="motivo" class="w-full rounded border border-indigo-300 bg-white px-3 py-2">
                        <option value="">Seleccionar</option>
                        @foreach($motivos as $clave => $m)
                            <option value="{{ $clave }}" @selected(old('motivo') === $clave)>{{ $m[0] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-indigo-900">Venta que reemplaza a esta (solo errores y cambios de producto en facturas)</label>
                    <input type="number" min="1" name="replacement_sale_id" value="{{ old('replacement_sale_id') }}" placeholder="N.º de la venta nueva, ya aceptada por Hacienda" class="w-full rounded border border-indigo-300 bg-white px-3 py-2">
                    <p class="mt-1 text-xs text-indigo-800">Hacienda exige emitir primero el documento corregido y luego invalidar el errado.</p>
                </div>
                <ul class="list-disc pl-5 text-xs text-indigo-900 space-y-1">
                    @if($dteInfo['tipo'] === '03')
                        <li><strong>Devolución, garantía, cambio de producto:</strong> Nota de Crédito por lo devuelto (total o parcial).</li>
                        <li><strong>Error de precio, descuento omitido:</strong> Nota de Crédito de valor (use "Devolver / ajustar" e indique el monto sin IVA por línea).</li>
                        <li><strong>Error de fecha o de datos del cliente:</strong> se invalida el CCF completo (use "Anular") con la venta que lo reemplaza.</li>
                    @else
                        <li><strong>Devolución parcial</strong> (garantía, cambio de producto o devolución de algunas unidades): Evento de Retorno por lo devuelto, hasta 3 meses después de la fecha de la factura. Si hay cambio de producto, emita aparte la venta nueva.</li>
                        <li><strong>Devolución total sin retornos previos:</strong> invalidación por rescisión. <strong>Errores de precio, fecha o datos, y cambio total de producto:</strong> invalidación con la venta que reemplaza.</li>
                        <li>Una factura con Evento de Retorno vigente ya no puede invalidarse.</li>
                    @endif
                    <li>Desperfecto y garantía son clasificaciones internas: no se transmiten a Hacienda.</li>
                </ul>
            </div>
        @endif

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
            <p class="text-xs text-gray-500">Estado del producto: sin abrir vuelve a inventario; dañado, caducado o con desperfecto pasa a no vendible.</p>

            <div class="overflow-hidden rounded border">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Producto</th>
                            <th class="px-3 py-2 text-right">Por devolver</th>
                            <th class="px-3 py-2 text-right">Devolver</th>
                            <th class="px-3 py-2 text-left">Estado</th>
                            @if($dteInfo)<th class="px-3 py-2 text-right">Acreditar sin IVA (solo ajustes de valor)</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($sale->items as $index => $item)
                            <tr>
                                <td class="px-3 py-2">
                                    {{ $item->product->name ?? ('#' . $item->product_id) }}
                                    <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}">
                                </td>
                                <td class="px-3 py-2 text-right">{{ (int) ($remaining[$item->id] ?? $item->quantity) }}</td>
                                <td class="px-3 py-2">
                                    <input type="number" min="0" max="{{ (int) ($remaining[$item->id] ?? $item->quantity) }}" name="items[{{ $index }}][quantity]" value="{{ old('items.' . $index . '.quantity', 0) }}" class="w-24 rounded border border-gray-300 px-2 py-1 text-right">
                                </td>
                                <td class="px-3 py-2">
                                    <select name="items[{{ $index }}][condition]" class="rounded border border-gray-300 px-2 py-1">
                                        <option value="">Seleccionar</option>
                                        <option value="unopened" @selected(old('items.' . $index . '.condition') === 'unopened')>Sin abrir</option>
                                        <option value="damaged" @selected(old('items.' . $index . '.condition') === 'damaged')>Dañado</option>
                                        <option value="expired" @selected(old('items.' . $index . '.condition') === 'expired')>Vencido/Caducado</option>
                                        <option value="defective" @selected(old('items.' . $index . '.condition') === 'defective')>Desperfecto (garantía)</option>
                                    </select>
                                </td>
                                @if($dteInfo)
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" min="0" name="items[{{ $index }}][credit_amount]" value="{{ old('items.' . $index . '.credit_amount') }}" class="w-28 rounded border border-gray-300 px-2 py-1 text-right">
                                    </td>
                                @endif
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
