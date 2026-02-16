@extends('layouts.app')

@section('title', 'Dashboard')
@section('content')
{{-- @yield('scripts') --}}


<h1 class="text-2xl font-bold mb-6">Dashboard de Ventas</h1>

<div class="grid grid-cols-4 gap-4 mb-8">

    <div class="bg-white p-4 shadow rounded">
        <p class="text-sm">Ventas Hoy</p>
        <p class="text-xl font-bold">${{ number_format($todaySales, 2) }}</p>
    </div>

    <div class="bg-white p-4 shadow rounded">
        <p class="text-sm">Ventas del Mes</p>
        <p class="text-xl font-bold">${{ number_format($monthSales, 2) }}</p>
   </div>

    <div class="bg-white p-4 shadow rounded">
        <p class="text-sm">Total Ventas</p>
        <p class="text-xl font-bold">{{ $totalSalesCount }}</p>
    </div>

    <div class="bg-white p-4 shadow rounded">
        <p class="text-sm">Ticket Promedio</p>
        <p class="text-xl font-bold">${{ number_format($averageTicket, 2) }}</p>
    </div>

</div>

<h2 class="text-lg font-bold mb-2">Top Productos</h2>

<table class="w-full bg-white shadow rounded">
    <thead>
        <tr class="border-b">
            <th class="p-2 text-left">Producto</th>
            <th class="p-2 text-left">Cantidad Vendida</th>
        </tr>
    </thead>
    <tbody>
        @foreach($topProducts as $item)
            <tr class="border-b">
                <td class="p-2">{{ $item->product->name }}</td>
                <td class="p-2">{{ $item->total_sold }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2 class="text-lg font-bold mt-10 mb-4">Ventas Últimos 30 Días</h2>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div id="salesChart"></div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    var options = {
        chart: {
            type: 'area',
            height: 350
        },
        series: [{
            name: 'Ventas',
            data: @json($totals)
        }],
        xaxis: {
            categories: @json($dates)
        },
        stroke: {
            curve: 'smooth'
        },
        tooltip: {
            y: {
                formatter: function (value) {
                    return "$ " + value.toFixed(2);
                }
            }
        }
    };

    var chart = new ApexCharts(
        document.querySelector("#salesChart"),
        options
    );

    chart.render();

});
</script>
@endsection


@endsection