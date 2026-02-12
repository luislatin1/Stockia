@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white p-4 shadow rounded">Ventas del día</div>
        <div class="bg-white p-4 shadow rounded">Productos en inventario</div>
        <div class="bg-white p-4 shadow rounded">Alertas de stock</div>
    </div>
@endsection
