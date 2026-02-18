@extends('layouts.app')

@section('title', 'Configuración Inicial')

@section('content')
<div class="max-w-2xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Paso 1: Datos del negocio</h2>
        <p class="text-sm text-gray-500">Completa la información principal para crear tu empresa.</p>
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

    <form method="POST" action="{{ route('setup.step1.store') }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">Nombre comercial</label>
            <input name="name" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('name') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Razón social</label>
            <input name="legal_name" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('legal_name') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">NIT / RFC / Tax ID</label>
            <input name="tax_id" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('tax_id') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">NIT (DTE)</label>
            <input name="nit" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('nit') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">NRC</label>
            <input name="nrc" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('nrc') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Código actividad</label>
            <input name="cod_actividad" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('cod_actividad') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Descripción actividad</label>
            <input name="desc_actividad" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('desc_actividad') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Tipo establecimiento</label>
            <input name="tipo_establecimiento" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('tipo_establecimiento') }}" placeholder="Ej: 01">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Teléfono</label>
            <input name="telefono" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('telefono') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Correo</label>
            <input name="correo" type="email" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('correo') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Departamento</label>
            <input name="departamento" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('departamento') }}" placeholder="Código CAT">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Municipio</label>
            <input name="municipio" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('municipio') }}" placeholder="Código CAT">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">Dirección complemento</label>
            <input name="direccion_complemento" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('direccion_complemento') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Moneda</label>
            <select name="currency_id" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}" {{ (string) old('currency_id') === (string) $currency->id ? 'selected' : '' }}>
                        {{ $currency->code }} - {{ $currency->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Zona horaria</label>
            <input name="timezone" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('timezone', 'America/El_Salvador') }}">
        </div>
        <button class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Continuar</button>
    </form>
</div>
@endsection
