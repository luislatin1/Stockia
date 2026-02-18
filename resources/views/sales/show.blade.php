@extends('layouts.app')

@section('title', 'Detalle de Venta')

@section('content')
@php
    $isAdminRole = in_array(function_exists('currentRole') ? currentRole() : null, ['Admin', 'SuperAdmin'], true);
@endphp
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Venta #{{ $sale->id }}</h2>
            <p class="text-sm text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('sales.ticket', $sale) }}" target="_blank" class="rounded border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700">
                Ticket
            </a>
            <a href="{{ route('sales.ticket.pdf', $sale) }}" target="_blank" class="rounded border border-sky-200 bg-white px-3 py-2 text-sm font-semibold text-sky-700">
                Ticket PDF
            </a>
            <a href="{{ route('sales.invoice', $sale) }}" target="_blank" class="rounded border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">
                Factura
            </a>
            <a href="{{ route('sales.invoice.pdf', $sale) }}" target="_blank" class="rounded border border-indigo-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700">
                Factura PDF
            </a>
            @if($sale->status === 'completed')
                <a href="{{ route('sales.admin-adjustment', $sale) }}" class="rounded bg-red-600 px-3 py-2 text-sm font-semibold text-white">
                    {{ $isAdminRole ? 'Cancelar / Anular' : 'Solicitar Cancelacion' }}
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Estado</p>
            <p class="mt-1 font-semibold">{{ ucfirst($sale->status) }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Método de Pago</p>
            <p class="mt-1 font-semibold">{{ strtoupper($sale->payment_method ?? 'cash') }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Comprobante</p>
            <p class="mt-1 font-semibold">{{ ucfirst($sale->document_type ?? 'ticket') }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Vendedor</p>
            <p class="mt-1 font-semibold">{{ $sale->user->name ?? 'N/A' }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Almacén</p>
            <p class="mt-1 font-semibold">{{ $sale->warehouse->name ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Producto</th>
                    <th class="px-4 py-3 text-right">Cantidad</th>
                    <th class="px-4 py-3 text-right">Precio Base</th>
                    <th class="px-4 py-3 text-right">Subtotal Base</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($sale->items as $item)
                    <tr>
                        <td class="px-4 py-3">{{ $item->product->name }}</td>
                        <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format($item->price, 2) }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 text-sm">
                <tr>
                    <td colspan="3" class="px-4 py-2 text-right font-medium">Subtotal</td>
                    <td class="px-4 py-2 text-right">${{ number_format($sale->subtotal ?? $sale->total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="px-4 py-2 text-right font-medium">IVA 13%</td>
                    <td class="px-4 py-2 text-right">${{ number_format($sale->tax_total ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="px-4 py-2 text-right font-bold">Total</td>
                    <td class="px-4 py-2 text-right font-bold">${{ number_format($sale->total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="px-4 py-2 text-right font-medium">Efectivo Recibido</td>
                    <td class="px-4 py-2 text-right">${{ number_format($sale->cash_received ?? $sale->total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="px-4 py-2 text-right font-medium">Cambio</td>
                    <td class="px-4 py-2 text-right">${{ number_format($sale->change_amount ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if(($adjustments ?? collect())->isNotEmpty())
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Historial de Cancelacion / Anulacion</h3>
            <div class="mt-3 space-y-3">
                @foreach($adjustments as $adjustment)
                    <div class="rounded border border-gray-200 bg-gray-50 p-3 text-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold text-gray-900">
                                {{ $adjustment->action_type === 'cancel_sale' ? 'Cancelacion de venta' : 'Anulacion de factura' }}
                            </p>
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($adjustment->created_at)->format('d/m/Y H:i') }}</p>
                        </div>
                        <p class="text-xs text-gray-600">Procesado por: {{ $adjustment->processed_by_name ?? ('Usuario #' . $adjustment->processed_by_user_id) }}</p>
                        <p class="text-xs text-gray-600">Devolucion de efectivo: {{ $adjustment->refund_cash ? '$' . number_format((float) $adjustment->refund_amount, 2) : 'No' }}</p>
                        <p class="text-xs text-gray-600">Devolucion de productos: {{ $adjustment->return_products ? 'Si' : 'No' }}</p>
                        <p class="mt-1 text-xs text-gray-700">Motivo/Nota: {{ $adjustment->notes ?: 'Sin observaciones.' }}</p>

                        @php($itemsForAdjustment = ($adjustmentItems[$adjustment->id] ?? collect()))
                        @if($itemsForAdjustment->isNotEmpty())
                            <div class="mt-2 overflow-hidden rounded border bg-white">
                                <table class="min-w-full text-xs">
                                    <thead class="bg-gray-100 text-gray-600">
                                        <tr>
                                            <th class="px-2 py-1 text-left">Producto</th>
                                            <th class="px-2 py-1 text-right">Cantidad</th>
                                            <th class="px-2 py-1 text-left">Estado</th>
                                            <th class="px-2 py-1 text-left">Destino</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($itemsForAdjustment as $itemAdjustment)
                                            <tr class="border-t">
                                                <td class="px-2 py-1">{{ $itemAdjustment->product_name ?? ('#' . $itemAdjustment->product_id) }}</td>
                                                <td class="px-2 py-1 text-right">{{ (int) $itemAdjustment->quantity }}</td>
                                                <td class="px-2 py-1">
                                                    @if($itemAdjustment->product_condition === 'unopened') Sin abrir
                                                    @elseif($itemAdjustment->product_condition === 'damaged') Dañado
                                                    @elseif($itemAdjustment->product_condition === 'expired') Vencido/Caducado
                                                    @else {{ $itemAdjustment->product_condition }}
                                                    @endif
                                                </td>
                                                <td class="px-2 py-1">{{ $itemAdjustment->restocked ? 'Inventario' : 'No vendibles' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
