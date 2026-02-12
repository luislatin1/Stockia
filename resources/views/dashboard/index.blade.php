@extends('layouts.app')

@section('content')

<h1 class="text-2xl font-bold mb-6">Dashboard</h1>

<div class="grid grid-cols-3 gap-6 mb-8">

    <div class="bg-white p-4 rounded shadow">
        <h2 class="text-gray-500">Ventas Hoy</h2>
        <p class="text-2xl font-bold">${{ number_format($todaySales, 2) }}</p>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <h2 class="text-gray-500">Ventas Mes</h2>
        <p class="text-2xl font-bold">${{ number_format($monthSales, 2) }}</p>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <h2 class="text-gray-500">Total Histórico</h2>
        <p class="text-2xl font-bold">${{ number_format($totalSales, 2) }}</p>
    </div>

</div>

<div class="bg-white p-4 rounded shadow mb-6">
    <h2 class="font-bold mb-3">Productos con Stock Bajo</h2>

    @forelse($lowStock as $product)
        <div class="text-red-600">
            {{ $product->name }} — Stock: {{ $product->stock }}
        </div>
    @empty
        <p>No hay productos con stock bajo.</p>
    @endforelse
</div>

@endsection
