@extends('layouts.app')

@section('title', 'Administración Central')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Administración Central (CORE)</h1>
        <p class="text-sm text-gray-500">Gestiona empresa, datos fiscales, branding del sistema y almacenes desde un solo lugar.</p>
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
        <div class="xl:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Empresa y Configuración Fiscal</h2>
                <p class="mt-1 text-sm text-gray-500">Estos datos se pueden usar en facturas, tickets y encabezados del sistema.</p>

                <form action="{{ route('core.admin.company.update') }}" method="POST" enctype="multipart/form-data" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre comercial</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $company?->name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label for="system_name" class="mb-1 block text-sm font-medium text-gray-700">Nombre del sistema</label>
                        <input id="system_name" name="system_name" type="text" value="{{ old('system_name', $company?->system_name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Ej: Stockia POS">
                    </div>

                    <div>
                        <label for="legal_name" class="mb-1 block text-sm font-medium text-gray-700">Razón social</label>
                        <input id="legal_name" name="legal_name" type="text" value="{{ old('legal_name', $company?->legal_name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label for="tax_id" class="mb-1 block text-sm font-medium text-gray-700">NIT / RFC / Tax ID</label>
                        <input id="tax_id" name="tax_id" type="text" value="{{ old('tax_id', $company?->tax_id) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label for="fiscal_email" class="mb-1 block text-sm font-medium text-gray-700">Email fiscal</label>
                        <input id="fiscal_email" name="fiscal_email" type="email" value="{{ old('fiscal_email', $company?->fiscal_email) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label for="fiscal_phone" class="mb-1 block text-sm font-medium text-gray-700">Teléfono fiscal</label>
                        <input id="fiscal_phone" name="fiscal_phone" type="text" value="{{ old('fiscal_phone', $company?->fiscal_phone) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div class="md:col-span-2">
                        <label for="fiscal_address" class="mb-1 block text-sm font-medium text-gray-700">Dirección fiscal</label>
                        <input id="fiscal_address" name="fiscal_address" type="text" value="{{ old('fiscal_address', $company?->fiscal_address) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label for="fiscal_regime" class="mb-1 block text-sm font-medium text-gray-700">Régimen fiscal</label>
                        <input id="fiscal_regime" name="fiscal_regime" type="text" value="{{ old('fiscal_regime', $company?->fiscal_regime) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label for="invoice_prefix" class="mb-1 block text-sm font-medium text-gray-700">Prefijo de factura</label>
                        <input id="invoice_prefix" name="invoice_prefix" type="text" value="{{ old('invoice_prefix', $company?->invoice_prefix) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Ej: FAC">
                    </div>

                    <div>
                        <label for="currency_id" class="mb-1 block text-sm font-medium text-gray-700">Moneda</label>
                        <select id="currency_id" name="currency_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}" {{ (string) old('currency_id', $company?->currency_id) === (string) $currency->id ? 'selected' : '' }}>
                                    {{ $currency->code }} - {{ $currency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="timezone" class="mb-1 block text-sm font-medium text-gray-700">Zona horaria</label>
                        <input id="timezone" name="timezone" type="text" value="{{ old('timezone', $company?->timezone) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label for="logo" class="mb-1 block text-sm font-medium text-gray-700">Logo de empresa</label>
                        <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,.svg" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>

                    @if ($company?->logo_path)
                        <div class="flex items-end">
                            <img src="{{ Storage::disk('public')->url($company->logo_path) }}" alt="Logo empresa" class="h-16 w-16 rounded-lg border border-gray-200 object-contain p-1">
                        </div>
                    @endif

                    <div class="md:col-span-2">
                        <label for="ticket_footer" class="mb-1 block text-sm font-medium text-gray-700">Pie de ticket</label>
                        <textarea id="ticket_footer" name="ticket_footer" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Gracias por su compra...">{{ old('ticket_footer', $company?->ticket_footer) }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="xl:col-span-1">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Nuevo Almacén</h2>
                <p class="mt-1 text-sm text-gray-500">Se asigna automáticamente a los usuarios de la empresa actual.</p>

                <form action="{{ route('core.admin.warehouses.store') }}" method="POST" class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <label for="warehouse_name" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
                        <input id="warehouse_name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Ej: Principal">
                    </div>

                    <div>
                        <label for="warehouse_code" class="mb-1 block text-sm font-medium text-gray-700">Código</label>
                        <input id="warehouse_code" name="code" type="text" value="{{ old('code') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Ej: MAIN-1">
                    </div>

                    <div>
                        <label for="warehouse_location" class="mb-1 block text-sm font-medium text-gray-700">Ubicación</label>
                        <input id="warehouse_location" name="location" type="text" value="{{ old('location') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
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
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Código</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Ubicación</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Estado</th>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No hay almacenes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
