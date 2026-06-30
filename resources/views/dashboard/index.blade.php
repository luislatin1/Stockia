@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- ── STAT CARDS ─────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Ventas Hoy --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Ventas Hoy</p>
            <span class="text-lg leading-none">💵</span>
        </div>
        <p class="text-3xl font-bold text-emerald-600 leading-none">${{ number_format($todaySales, 2) }}</p>
        <p class="text-xs text-gray-400">Ingresos del día en curso</p>
    </div>

    {{-- Ventas del Mes --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Ventas del Mes</p>
            <span class="text-lg leading-none">📅</span>
        </div>
        <p class="text-3xl font-bold text-indigo-600 leading-none">${{ number_format($monthSales, 2) }}</p>
        <p class="text-xs text-gray-400">{{ now()->translatedFormat('F Y') }}</p>
    </div>

    {{-- Total de Ventas --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Total de Ventas</p>
            <span class="text-lg leading-none">🧾</span>
        </div>
        <p class="text-3xl font-bold text-gray-900 leading-none">{{ number_format($totalSalesCount) }}</p>
        <p class="text-xs text-gray-400">Transacciones completadas</p>
    </div>

    {{-- Ticket Promedio --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Ticket Promedio</p>
            <span class="text-lg leading-none">📊</span>
        </div>
        <p class="text-3xl font-bold text-gray-900 leading-none">${{ number_format($averageTicket, 2) }}</p>
        <p class="text-xs text-gray-400">Por transacción</p>
    </div>

</div>

{{-- ── FILA INFERIOR: Top Productos + Gráfico ─────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Top Productos --}}
    <div class="lg:col-span-1 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">🏆 Top Productos</h2>
            <span class="text-[11px] text-gray-400">últimos 30 días</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-400 bg-gray-50">
                    <th class="px-4 py-2 text-left font-semibold">Producto</th>
                    <th class="px-4 py-2 text-right font-semibold">Unidades</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($topProducts as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-2.5 text-gray-800 truncate max-w-[160px]">{{ $item->product->name }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold text-gray-900">{{ $item->total_sold }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-8 text-center text-sm text-gray-400">Sin datos aún</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Gráfico Últimos 30 días --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700">📈 Ventas — Últimos 30 días</h2>
            @if(count($dates) > 0)
                <span class="text-[11px] text-gray-400">{{ count($dates) }} días con ventas</span>
            @endif
        </div>
        <div class="p-4">
            @if(count($totals) > 0)
                <div id="salesChart"></div>
            @else
                <div class="flex flex-col items-center justify-center py-16 text-gray-300">
                    <span class="text-4xl mb-3">📉</span>
                    <p class="text-sm font-medium text-gray-400">Sin ventas en los últimos 30 días</p>
                </div>
            @endif
        </div>
    </div>

</div>

@endsection

@section('scripts')
@if(count($totals) > 0)
<script>
document.addEventListener('DOMContentLoaded', function () {
    new ApexCharts(document.querySelector('#salesChart'), {
        chart: {
            type: 'area',
            height: 260,
            toolbar: { show: false },
            fontFamily: 'Figtree, sans-serif',
            sparkline: { enabled: false },
        },
        colors: ['#4f46e5'],
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.2, opacityTo: 0.01 },
        },
        series: [{ name: 'Ventas', data: @json($totals) }],
        xaxis: {
            categories: @json($dates),
            labels: {
                rotate: -35,
                style: { fontSize: '10px', colors: '#9ca3af' },
                formatter: v => {
                    const [y, m, d] = v.split('-');
                    return d + '/' + m;
                },
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: {
                formatter: v => '$' + v.toFixed(0),
                style: { fontSize: '11px', colors: '#9ca3af' },
            },
        },
        grid: { borderColor: '#f3f4f6', strokeDashArray: 4, padding: { left: 4, right: 4 } },
        stroke: { curve: 'smooth', width: 2 },
        tooltip: {
            y: { formatter: v => '$' + v.toFixed(2) },
            theme: 'light',
        },
        dataLabels: { enabled: false },
        markers: { size: 3, colors: ['#4f46e5'], strokeWidth: 0 },
    }).render();
});
</script>
@endif
@endsection
