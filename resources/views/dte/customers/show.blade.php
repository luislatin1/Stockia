@extends('layouts.app')

@section('title', 'Detalle Cliente DTE')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $customer->nombre }}</h2>
            <p class="text-sm text-gray-500">Detalle fiscal del receptor.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dte.customers.edit', $customer) }}" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700">Editar</a>
            <a href="{{ route('dte.customers.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Volver</a>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div><dt class="text-xs uppercase text-gray-500">Tipo documento</dt><dd class="text-sm font-medium text-gray-900">{{ $customer->tipo_documento }}</dd></div>
            <div><dt class="text-xs uppercase text-gray-500">Número documento</dt><dd class="text-sm font-medium text-gray-900">{{ $customer->numero_documento }}</dd></div>
            <div><dt class="text-xs uppercase text-gray-500">NRC</dt><dd class="text-sm font-medium text-gray-900">{{ $customer->nrc ?: 'N/A' }}</dd></div>
            <div><dt class="text-xs uppercase text-gray-500">Contribuyente</dt><dd class="text-sm font-medium text-gray-900">{{ $customer->es_contribuyente ? 'Sí' : 'No' }}</dd></div>
            <div><dt class="text-xs uppercase text-gray-500">Departamento</dt><dd class="text-sm font-medium text-gray-900">{{ $customer->departamento ?: 'N/A' }}</dd></div>
            <div><dt class="text-xs uppercase text-gray-500">Municipio</dt><dd class="text-sm font-medium text-gray-900">{{ $customer->municipio ?: 'N/A' }}</dd></div>
            <div class="md:col-span-2"><dt class="text-xs uppercase text-gray-500">Dirección</dt><dd class="text-sm font-medium text-gray-900">{{ $customer->direccion ?: 'N/A' }}</dd></div>
            <div><dt class="text-xs uppercase text-gray-500">Teléfono</dt><dd class="text-sm font-medium text-gray-900">{{ $customer->telefono ?: 'N/A' }}</dd></div>
            <div><dt class="text-xs uppercase text-gray-500">Correo</dt><dd class="text-sm font-medium text-gray-900">{{ $customer->correo ?: 'N/A' }}</dd></div>
        </dl>
    </div>
</div>
@endsection
