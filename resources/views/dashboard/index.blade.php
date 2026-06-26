@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- Stat cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <p class="text-[11px] uppercase tracking-widest font-semibold text-gray-400 mb-2">VENTAS HOY</p>
        <p class="text-2xl font-bold text-gray-900">${{ number_format($todaySales, 2) }}</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <p class="text-[11px] uppercase tracking-widest font-semibold text-gray-400 mb-2">VENTAS DEL MES</p>
        <p class="text-2xl font-bold text-gray-900">${{ number_format($monthSales, 2) }}</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <p class="text-[11px] uppercase tracking-widest font-semibold text-gray-400 mb-2">TOTAL DE VENTAS</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($totalSalesCount) }}</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <p class="text-[11px] uppercase tracking-widest font-semibold text-gray-400 mb-2">TICKET PROMEDIO</p>
        <p class="text-2xl font-bold text-gray-900">${{ number_format($averageTicket, 2) }}</p>
    </div>

</div>

{{-- Top productos + Gráfico --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Top productos --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-700">Top Productos</h2>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500 bg-gray-50">
                        <th class="px-4 py-2 text-left font-semibold">Producto</th>
                        <th class="px-4 py-2 text-right font-semibold">Vendidos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($topProducts as $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-2.5 text-gray-800">{{ $item->product->name }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-gray-900">{{ $item->total_sold }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-6 text-center text-sm text-gray-400">Sin datos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Gráfico de ventas --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-700">Ventas — Últimos 30 días</h2>
            </div>
            <div class="p-4">
                <div id="salesChart"></div>
            </div>
        </div>
    </div>

</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    new ApexCharts(document.querySelector('#salesChart'), {
        chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Figtree, sans-serif' },
        colors: ['#4f46e5'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0.02 } },
        series: [{ name: 'Ventas', data: @json($totals) }],
        xaxis: { categories: @json($dates), labels: { style: { fontSize: '11px', colors: '#9ca3af' } }, axisBorder: { show: false } },
        yaxis: { labels: { formatter: v => '$ ' + v.toFixed(2), style: { fontSize: '11px', colors: '#9ca3af' } } },
        grid: { borderColor: '#f3f4f6', strokeDashArray: 4 },
        stroke: { curve: 'smooth', width: 2 },
        tooltip: { y: { formatter: v => '$ ' + v.toFixed(2) } },
        dataLabels: { enabled: false },
    }).render();
});
</script>
@endsection
