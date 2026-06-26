@extends('layouts.app')

@section('title', 'Configuración Inicial')

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const department = document.getElementById('departamento');
    const municipality = document.getElementById('municipio');
    const actividadSelect = document.getElementById('cod_actividad');

    // Cascada departamento → municipio
    if (department && municipality) {
        const filterMunicipalities = () => {
            const selectedDep = department.value;
            let hasVisible = false;

            Array.from(municipality.options).forEach((option) => {
                if (option.value === '') { option.hidden = false; return; }
                const optDep = option.getAttribute('data-departamento');
                const visible = selectedDep !== '' && optDep === selectedDep;
                option.hidden = !visible;
                if (visible) hasVisible = true;
            });

            const sel = municipality.options[municipality.selectedIndex];
            if (sel && sel.value !== '' && sel.hidden) municipality.value = '';
            municipality.disabled = selectedDep === '' || !hasVisible;
        };

        department.addEventListener('change', filterMunicipalities);
        filterMunicipalities();
    }
});
</script>
@endsection

@section('content')
@php
    $selectedDep = (string) old('departamento', '');
@endphp
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
            <label class="block text-sm font-medium text-gray-700">Nombre comercial <span class="text-red-500">*</span></label>
            <input name="name" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('name') }}" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Razón social</label>
            <input name="legal_name" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('legal_name') }}">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">NIT</label>
                <input name="nit" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('nit') }}" placeholder="0000-000000-000-0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">NRC</label>
                <input name="nrc" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('nrc') }}" placeholder="000000-0">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Actividad económica</label>
            <select id="cod_actividad" name="cod_actividad" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2">
                <option value="">— Selecciona actividad —</option>
                @foreach ($actividades as $act)
                    <option value="{{ $act->codigo }}" {{ old('cod_actividad') === $act->codigo ? 'selected' : '' }}>
                        {{ $act->codigo }} — {{ $act->descripcion }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Tipo de establecimiento</label>
            <select name="tipo_establecimiento" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2">
                <option value="">— Selecciona tipo —</option>
                @foreach ($tiposEstablecimiento as $tipo)
                    <option value="{{ $tipo->codigo }}" {{ old('tipo_establecimiento') === $tipo->codigo ? 'selected' : '' }}>
                        {{ $tipo->codigo }} — {{ $tipo->descripcion }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Departamento</label>
                <select id="departamento" name="departamento" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2">
                    <option value="">— Selecciona —</option>
                    @foreach ($departamentos as $dep)
                        <option value="{{ $dep->codigo }}" {{ $selectedDep === $dep->codigo ? 'selected' : '' }}>
                            {{ $dep->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Municipio</label>
                <select id="municipio" name="municipio" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2" disabled>
                    <option value="">— Selecciona —</option>
                    @foreach ($municipios as $mun)
                        <option
                            value="{{ $mun->codigo }}"
                            data-departamento="{{ $mun->departamento_codigo }}"
                            {{ (string) old('municipio') === (string) $mun->codigo ? 'selected' : '' }}
                        >{{ $mun->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Dirección complemento</label>
            <input name="direccion_complemento" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('direccion_complemento') }}">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                <input name="telefono" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('telefono') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Correo</label>
                <input name="correo" type="email" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('correo') }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Moneda <span class="text-red-500">*</span></label>
                <select name="currency_id" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2">
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency->id }}" {{ (string) old('currency_id') === (string) $currency->id ? 'selected' : '' }}>
                            {{ $currency->code }} — {{ $currency->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Zona horaria</label>
                <input name="timezone" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('timezone', 'America/El_Salvador') }}">
            </div>
        </div>

        <button class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Continuar →</button>
    </form>
</div>
@endsection
