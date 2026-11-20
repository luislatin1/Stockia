@csrf
@if(isset($method) && $method === 'PUT')
    @method('PUT')
@endif

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Tipo documento (CAT-022)</label>
        <select name="tipo_documento" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Seleccionar</option>
            @foreach(($documentTypes ?? collect()) as $doc)
                <option value="{{ $doc->codigo }}" @selected(old('tipo_documento', $customer->tipo_documento ?? '') === $doc->codigo)>
                    {{ $doc->codigo }} - {{ $doc->descripcion }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Número documento</label>
        <input name="numero_documento" value="{{ old('numero_documento', $customer->numero_documento ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">NRC</label>
        <input name="nrc" value="{{ old('nrc', $customer->nrc ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
        <input name="nombre" value="{{ old('nombre', $customer->nombre ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Departamento</label>
        <select name="departamento" id="departamento" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Seleccionar</option>
            @foreach($departments as $department)
                <option value="{{ $department->codigo }}" @selected(old('departamento', $customer->departamento ?? '') === $department->codigo)>
                    {{ $department->codigo }} - {{ $department->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Municipio</label>
        <select name="municipio" id="municipio" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Seleccionar</option>
            @foreach($municipalities as $municipality)
                <option value="{{ $municipality->codigo_local }}" data-dept="{{ $municipality->departamento_codigo }}" @selected(old('municipio', $customer->municipio ?? '') === $municipality->codigo_local)>
                    {{ $municipality->codigo_local }} - {{ $municipality->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Distrito</label>
        <select name="distrito" id="distrito" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Seleccionar</option>
            @foreach($distritos as $distrito)
                <option value="{{ $distrito->codigo }}" data-dept="{{ $distrito->departamento_codigo }}" @selected((string) old('distrito', $customer->distrito ?? '') === (string) $distrito->codigo)>
                    {{ $distrito->codigo }} - {{ $distrito->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Código de actividad económica (CAT-019)</label>
        <input name="cod_actividad" inputmode="numeric" maxlength="6" value="{{ old('cod_actividad', $customer->cod_actividad ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <p class="mt-1 text-xs text-gray-500">5 o 6 dígitos. Obligatorio para emitir Crédito Fiscal (03).</p>
        @error('cod_actividad')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Actividad económica</label>
        <input name="desc_actividad" maxlength="150" value="{{ old('desc_actividad', $customer->desc_actividad ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        @error('desc_actividad')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2 rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-xs text-indigo-900">
        <strong>Cliente del extranjero (Factura de Exportación):</strong> código de país de 2 letras (CAT-020, p. ej. US, GT, MX), nombre del país y tipo de persona.
        Use tipo de documento Pasaporte (03), Carné de residente (02) u Otro (37).
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Código de país (CAT-020)</label>
        <input name="cod_pais" maxlength="2" value="{{ old('cod_pais', $customer->cod_pais ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase">
        @error('cod_pais')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">País</label>
        <input name="nombre_pais" maxlength="50" value="{{ old('nombre_pais', $customer->nombre_pais ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        @error('nombre_pais')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Tipo de persona (CAT-029)</label>
        <select name="tipo_persona" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">No aplica</option>
            <option value="1" @selected((string) old('tipo_persona', $customer->tipo_persona ?? '') === '1')>1 - Persona natural</option>
            <option value="2" @selected((string) old('tipo_persona', $customer->tipo_persona ?? '') === '2')>2 - Persona jurídica</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-gray-700">Dirección</label>
        <input name="direccion" value="{{ old('direccion', $customer->direccion ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Teléfono</label>
        <input name="telefono" value="{{ old('telefono', $customer->telefono ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Correo</label>
        <input name="correo" type="email" value="{{ old('correo', $customer->correo ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="es_contribuyente" value="1" {{ old('es_contribuyente', $customer->es_contribuyente ?? false) ? 'checked' : '' }}>
            <span class="text-sm text-gray-700">Es contribuyente</span>
        </label>
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('dte.customers.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
        Cancelar
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dept = document.getElementById('departamento');
    const muni = document.getElementById('municipio');
    if (!dept || !muni) return;

    const applyFilter = () => {
        const selectedDept = dept.value;
        Array.from(muni.options).forEach((opt) => {
            if (!opt.value) return;
            const visible = !selectedDept || opt.dataset.dept === selectedDept;
            opt.hidden = !visible;
        });

        if (muni.selectedOptions.length && muni.selectedOptions[0].hidden) {
            muni.value = '';
        }
    };

    const dist = document.getElementById('distrito');
    const applyDistrict = () => {
        if (!dist) return;
        Array.from(dist.options).forEach((opt) => {
            if (!opt.value) return;
            opt.hidden = !dept.value || opt.dataset.dept !== dept.value;
        });
        if (dist.selectedOptions.length && dist.selectedOptions[0].hidden) dist.value = '';
    };

    dept.addEventListener('change', () => { applyFilter(); applyDistrict(); });
    applyFilter();
    applyDistrict();
});
</script>
