@extends('layouts.app')

@section('title', 'Almacenes')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Almacenes</h1>
        <p class="text-sm text-gray-500">Crea y administra almacenes de la empresa seleccionada.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-1">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Nuevo Almacén</h2>
                <p class="mt-1 text-sm text-gray-500">Quedará vinculado a la empresa actual.</p>

                <form action="{{ route('warehouses.store') }}" method="POST" class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Ej: Principal">
                    </div>

                    <div>
                        <label for="code" class="mb-1 block text-sm font-medium text-gray-700">Código</label>
                        <input id="code" name="code" type="text" value="{{ old('code') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Ej: MAIN-1">
                    </div>

                    <div>
                        <label for="location" class="mb-1 block text-sm font-medium text-gray-700">Ubicación</label>
                        <input id="location" name="location" type="text" value="{{ old('location') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Ej: San Salvador">
                    </div>

                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Activo</span>
                    </label>

                    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Crear Almacén
                    </button>
                </form>
            </div>
        </div>

        <div class="xl:col-span-2">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Ubicación</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Creado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($warehouses as $warehouse)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">{{ $warehouse->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $warehouse->code }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $warehouse->location ?: 'Sin ubicación' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if ($warehouse->is_active)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Activo</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ optional($warehouse->created_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No hay almacenes registrados para esta empresa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
