@extends('layouts.app')

@section('title', 'Apartados')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Apartados</h2>
        <a href="{{ route('customerorders.create') }}" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Nuevo Apartado
        </a>
    </div>

    @if (session('success'))
        <p class="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</p>
    @endif

    @forelse($orders as $order)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-2 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-gray-900">{{ $order->order_number ?: ('APT #' . $order->id) }}</p>
                    <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</p>
                    <p class="text-xs text-gray-500">Cliente: {{ $order->customer_name }}</p>
                    <p class="text-xs text-gray-500">Vendedor: {{ $order->user_name ?? ('#' . $order->user_id) }}</p>
                </div>
                <a href="{{ route('customerorders.show', $order->id) }}" class="text-sm font-semibold text-indigo-600">
                    Ver Detalles
                </a>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm md:grid-cols-5">
                <p><span class="text-gray-500">Entrega est.:</span> {{ $order->fecha_entrega_estimada ? \Carbon\Carbon::parse($order->fecha_entrega_estimada)->format('d/m/Y') : 'Sin fecha' }}</p>
                <p><span class="text-gray-500">Total:</span> ${{ number_format((float) $order->total, 2) }}</p>
                <p><span class="text-gray-500">Anticipo:</span> ${{ number_format((float) $order->anticipo, 2) }}</p>
                <p><span class="text-gray-500">Saldo:</span> ${{ number_format((float) $order->saldo_pendiente, 2) }}</p>
                <p>
                    <span class="text-gray-500">Estado:</span>
                    @php
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
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $badges[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $labels[$order->status] ?? ucfirst($order->status) }}
                    </span>
                </p>
            </div>
        </div>
    @empty
        <p class="rounded border border-dashed border-gray-300 bg-white p-6 text-center text-gray-500">No hay apartados aún.</p>
    @endforelse

    {{ $orders->links() }}
</div>
@endsection
