@extends('layouts.app')

@section('title', 'Compañias')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Compañías</h1>
        <p class="text-sm text-gray-500">Crea y consulta las compañías disponibles en el sistema.</p>
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
                <h2 class="text-lg font-semibold text-gray-900">Nueva Compañía</h2>
                <p class="mt-1 text-sm text-gray-500">Al crearla, quedarás asignado como <strong>SuperAdmin</strong>.</p>

                <form action="{{ route('companies.store') }}" method="POST" class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre comercial</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Ej: Stockia">
                    </div>

                    <div>
                        <label for="legal_name" class="mb-1 block text-sm font-medium text-gray-700">Razón social</label>
                        <input id="legal_name" name="legal_name" type="text" value="{{ old('legal_name') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Ej: Stockia S.A. de C.V.">
                    </div>

                    <div>
                        <label for="tax_id" class="mb-1 block text-sm font-medium text-gray-700">NIT / RFC / Tax ID</label>
                        <input id="tax_id" name="tax_id" type="text" value="{{ old('tax_id') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Identificación fiscal">
                    </div>

                    <div>
                        <label for="currency_id" class="mb-1 block text-sm font-medium text-gray-700">Moneda</label>
                        <select id="currency_id" name="currency_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <option value="">Selecciona moneda</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}" {{ (string) old('currency_id') === (string) $currency->id ? 'selected' : '' }}>
                                    {{ $currency->code }} - {{ $currency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="timezone" class="mb-1 block text-sm font-medium text-gray-700">Zona horaria</label>
                        <input id="timezone" name="timezone" type="text" value="{{ old('timezone', 'America/Mexico_City') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Ej: America/Mexico_City">
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Crear Compañía
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
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Moneda</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Zona Horaria</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Almacenes</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Creada</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($companies as $company)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $company->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $company->legal_name ?: 'Sin razón social' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                        {{ optional($company->currency)->code ?: 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $company->timezone }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $company->warehouses_count }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ optional($company->created_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No hay compañías registradas.</td>
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
