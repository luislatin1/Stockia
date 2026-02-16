@extends('layouts.app')

@section('title', 'Seleccionar Almacén')

@section('content')

<div class="max-w-md mx-auto mt-20 bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Seleccionar Almacén</h2>

    <form method="POST" action="{{ route('warehouse.select') }}">
        @csrf

        <select name="warehouse_id" class="w-full border p-2 mb-4 rounded">
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">
                    {{ $warehouse->name }}
                </option>
            @endforeach
        </select>

        <button class="bg-blue-600 text-white px-4 py-2 rounded w-full">
            Entrar
        </button>
    </form>
</div>

@endsection
