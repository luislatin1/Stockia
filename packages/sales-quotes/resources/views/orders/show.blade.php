@extends('layouts.app')

@section('title', 'Detalle Apartado')

@section('content')
@php
    $isAdminRole = in_array(function_exists('currentRole') ? currentRole() : null, ['Admin', 'SuperAdmin'], true);
    $badges = [
        'pendiente' => 'bg-amber-100 text-amber-700',
        'listo_para_entrega' => 'bg-blue-100 text-blue-700',
        'entregado' => 'bg-emerald-100 text-emerald-700',
        'cancelado' => 'bg-gray-200 text-gray-700',
    ];
    $labels = [
        'pendiente' => 'Pendiente',
        'listo_para_entrega' => 'Listo para entrega',
        'entregado' => 'Entregado',
        'cancelado' => 'Anulado',
    ];
@endphp
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $order->order_number ?: ('APT #' . $order->id) }}</h2>
            <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</p>
        </div>
        <a href="{{ route('customerorders.index') }}" class="rounded border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700">
            Volver
        </a>
    </div>

    @if (session('success'))
        <p class="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</p>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Cliente</p>
            <p class="mt-1 font-semibold">{{ $order->customer_name }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Estado</p>
            <p class="mt-1">
                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $badges[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ $labels[$order->status] ?? ucfirst($order->status) }}
                </span>
            </p>
        </div>
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Entrega estimada</p>
            <p class="mt-1 font-semibold">{{ $order->fecha_entrega_estimada ? \Carbon\Carbon::parse($order->fecha_entrega_estimada)->format('d/m/Y') : 'Sin fecha' }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Vendedor</p>
            <p class="mt-1 font-semibold">{{ $order->user_name ?? ('#' . $order->user_id) }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Producto</th>
                    <th class="px-4 py-3 text-right">Cantidad</th>
                    <th class="px-4 py-3 text-right">Precio</th>
                    <th class="px-4 py-3 text-right">Desc. %</th>
                    <th class="px-4 py-3 text-right">Desc. $</th>
                    <th class="px-4 py-3 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($items as $item)
                    <tr>
                        <td class="px-4 py-3">{{ $item->product_name }}</td>
                        <td class="px-4 py-3 text-right">{{ (int) $item->quantity }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format((float) $item->price, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) ($item->discount_percent ?? 0), 2) }}%</td>
                        <td class="px-4 py-3 text-right">${{ number_format((float) ($item->discount_amount ?? 0), 2) }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format((float) $item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 text-sm">
                <tr>
                    <td colspan="5" class="px-4 py-2 text-right font-medium">Subtotal</td>
                    <td class="px-4 py-2 text-right">${{ number_format((float) $order->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="px-4 py-2 text-right font-medium">IVA 13%</td>
                    <td class="px-4 py-2 text-right">${{ number_format((float) $order->tax_total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="px-4 py-2 text-right font-bold">Total</td>
                    <td class="px-4 py-2 text-right font-bold">${{ number_format((float) $order->total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="px-4 py-2 text-right font-medium text-emerald-700">Anticipo recibido</td>
                    <td class="px-4 py-2 text-right text-emerald-700">${{ number_format((float) $order->anticipo, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="px-4 py-2 text-right font-bold">Saldo pendiente</td>
                    <td class="px-4 py-2 text-right font-bold">${{ number_format((float) $order->saldo_pendiente, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <p class="mb-3 text-xs uppercase text-gray-500">Acciones</p>
        <div class="flex flex-wrap gap-3">
            @if($order->status === 'pendiente')
                <form method="POST" action="{{ route('customerorders.ready', $order->id) }}">
                    @csrf
                    <button class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Marcar listo para entrega</button>
                </form>
            @endif

            @if(in_array($order->status, ['pendiente', 'listo_para_entrega'], true))
                <form method="POST" action="{{ route('customerorders.deliver', $order->id) }}">
                    @csrf
                    <button class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Entregar al cliente</button>
                </form>

                @if($isAdminRole)
                    <form method="POST" action="{{ route('customerorders.cancel', $order->id) }}" onsubmit="return confirm('¿Anular este apartado? El stock reservado se liberará.');">
                        @csrf
                        <button class="rounded border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">Anular apartado</button>
                    </form>
                @endif
            @endif

            @if($order->status === 'entregado')
                <p class="text-sm text-gray-500">Entregado el {{ \Carbon\Carbon::parse($order->delivered_at)->format('d/m/Y H:i') }}.</p>
            @endif

            @if($order->status === 'cancelado')
                <p class="text-sm text-gray-500">Anulado el {{ \Carbon\Carbon::parse($order->cancelled_at)->format('d/m/Y H:i') }}. El stock reservado fue liberado.</p>
            @endif
        </div>
    </div>
</div>
@endsection
