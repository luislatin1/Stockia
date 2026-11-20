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

    @if ($sale->payment_method === 'credit')
        <div class="rounded-xl border bg-white p-4 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs uppercase text-gray-500">Saldo pendiente</p>
                    <p class="mt-1 text-xl font-bold {{ $sale->saldo_pendiente > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                        ${{ number_format($sale->saldo_pendiente, 2) }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase text-gray-500">Vence</p>
                    <p class="mt-1 font-semibold {{ $sale->esta_vencida ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $sale->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                        @if ($sale->esta_vencida)
                            <span class="text-xs">(vencida)</span>
                        @endif
                    </p>
                </div>
            </div>

            @if ($sale->payments->isNotEmpty())
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                            <th class="text-left py-1">Fecha</th>
                            <th class="text-left py-1">Forma</th>
                            <th class="text-left py-1">Referencia</th>
                            <th class="text-right py-1">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($sale->payments as $payment)
                            <tr>
                                <td class="py-1.5">{{ $payment->fecha->format('d/m/Y') }}</td>
                                <td class="py-1.5">{{ $payment->forma_pago }}</td>
                                <td class="py-1.5">{{ $payment->referencia ?: '—' }}</td>
                                <td class="py-1.5 text-right">${{ number_format($payment->monto, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($sale->saldo_pendiente > 0)
                <form action="{{ route('sales.payments.store', $sale) }}" method="POST"
                      class="grid grid-cols-4 gap-3 items-end border-t border-gray-100 pt-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Fecha</label>
                        <input type="date" name="fecha" value="{{ now()->format('Y-m-d') }}"
                               class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Monto</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $sale->saldo_pendiente }}" name="monto"
                               class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Forma de pago</label>
                        <select name="forma_pago" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Referencia</label>
                        <input type="text" name="referencia" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                    </div>
                    @error('monto')
                        <p class="col-span-4 text-red-500 text-xs">{{ $message }}</p>
                    @enderror
                    <div class="col-span-4">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                            Registrar cobro
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    @if(($adjustments ?? collect())->isNotEmpty())
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Historial de Cancelacion / Anulacion</h3>
            <div class="mt-3 space-y-3">
                @foreach($adjustments as $adjustment)
                    <div class="rounded border border-gray-200 bg-gray-50 p-3 text-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold text-gray-900">
                                {{ match($adjustment->action_type) { 'cancel_sale' => 'Cancelacion de venta', 'return_items' => 'Devolucion / ajuste', default => 'Anulacion de factura' } }}
                            </p>
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($adjustment->created_at)->format('d/m/Y H:i') }}</p>
                        </div>
                        <p class="text-xs text-gray-600">Procesado por: {{ $adjustment->processed_by_name ?? ('Usuario #' . $adjustment->processed_by_user_id) }}</p>
                        <p class="text-xs text-gray-600">Devolucion de efectivo: {{ $adjustment->refund_cash ? '$' . number_format((float) $adjustment->refund_amount, 2) : 'No' }}</p>
                        <p class="text-xs text-gray-600">Devolucion de productos: {{ $adjustment->return_products ? 'Si' : 'No' }}</p>
                        @if(! empty($adjustment->motivo))
                            <p class="text-xs text-gray-600">Motivo: {{ \Stockia\DteSvMh\Devolucion\RutaDevolucion::etiqueta($adjustment->motivo) }}
                                @if(! empty($adjustment->dte_ruta) && $adjustment->dte_ruta !== 'ninguna')
                                    &middot; Hacienda: {{ $adjustment->dte_ruta === 'nota_credito' ? 'Nota de Crédito emitida' : 'DTE invalidado' }}
                                @endif
                            </p>
                        @endif
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
                                                    @elseif($itemAdjustment->product_condition === 'defective') Desperfecto (garantía)
                                                    @elseif($itemAdjustment->product_condition === 'n/a') Ajuste de valor: ${{ number_format((float) $itemAdjustment->credit_amount, 2) }}
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
