@extends('layouts.app')

@section('title', 'Seleccionar Almacén')

@section('content')

<div class="max-w-md mx-auto mt-20 bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Seleccionar Almacén</h2>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if($warehouses->isEmpty())
        <div class="rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
            No tienes almacenes asignados en esta empresa. Solicita acceso a un administrador.
        </div>
    @else
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
    @endif
</div>

@endsection
