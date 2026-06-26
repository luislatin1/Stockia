@extends('layouts.app')

@section('title', 'Cambiar Almacén')

@section('content')
<div class="flex items-start justify-center pt-12">
    <div class="w-full max-w-sm">

        <div class="mb-6 text-center">
            <div class="text-4xl mb-2">🏪</div>
            <h1 class="text-2xl font-bold text-gray-900">Cambiar Almacén</h1>
            <p class="mt-1 text-sm text-gray-500">Selecciona el almacén con el que deseas trabajar.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if ($warehouses->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                ⚠ No tienes almacenes asignados en esta empresa. Solicita acceso a un administrador.
            </div>
        @else
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('warehouse.select') }}">
                    @csrf

                    <div class="space-y-2 mb-5">
                        @foreach ($warehouses as $warehouse)
                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 cursor-pointer hover:bg-gray-50 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                <input
                                    type="radio"
                                    name="warehouse_id"
                                    value="{{ $warehouse->id }}"
                                    class="accent-indigo-600"
                                    {{ $loop->first ? 'checked' : '' }}
                                >
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $warehouse->name }}</div>
                                    @if ($warehouse->location)
                                        <div class="text-xs text-gray-500">{{ $warehouse->location }}</div>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                        Entrar
                    </button>
                </form>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    ← Volver al dashboard
                </a>
            </div>
        @endif

    </div>
</div>
@endsection
