@extends('layouts.app')

@section('title', 'PTV-POS')

@section('content')
<div class="space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h2 class="text-xl font-bold text-gray-900">Resumen del cajero</h2>
        <p class="text-sm text-gray-500">Rendimiento de hoy en el almacen activo.</p>
        <p class="mt-2 text-sm">
            Estado de caja:
            @if($activeSession)
                <span class="font-semibold text-emerald-600">Abierta</span>
                <span class="text-gray-600">- {{ $activeSession->register_name ?? 'Sin nombre' }} ({{ $activeSession->register_code ?? 'N/A' }})</span>
            @else
                <span class="font-semibold text-amber-600">Cerrada</span>
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase text-gray-500">Ventas</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $salesCount }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase text-gray-500">Total vendido</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">${{ number_format($grossTotal, 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase text-gray-500">Efectivo recibido</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">${{ number_format($cashTotal, 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase text-gray-500">Ticket promedio</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">${{ number_format($avgTicket, 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase text-gray-500">Unidades vendidas</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $itemsSold }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <h3 class="font-semibold text-gray-900">Ultimas ventas</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Venta</th>
                            <th class="px-4 py-2 text-left">Documento</th>
                            <th class="px-4 py-2 text-right">Total</th>
                            <th class="px-4 py-2 text-left">Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestSales as $sale)
                            <tr class="border-t">
                                <td class="px-4 py-2">#{{ $sale->id }}</td>
                                <td class="px-4 py-2">{{ strtoupper($sale->document_type) }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format((float) $sale->total, 2) }}</td>
                                <td class="px-4 py-2">{{ $sale->created_at->format('H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-gray-500">Sin ventas registradas hoy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-3">
                <h3 class="font-semibold text-gray-900">Top productos</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Producto</th>
                            <th class="px-4 py-2 text-right">Unidades</th>
                            <th class="px-4 py-2 text-right">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $row)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $row->name }}</td>
                                <td class="px-4 py-2 text-right">{{ (int) $row->qty }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format((float) $row->subtotal, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-gray-500">Sin datos de ventas para mostrar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
