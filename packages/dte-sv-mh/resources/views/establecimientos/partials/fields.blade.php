{{-- Campos compartidos de crear/editar establecimiento. $e = modelo o null, $uid = id único. --}}
<div>
    <label class="mb-1 block text-sm font-medium text-gray-700">Código (M/B/S/P + 3 dígitos)</label>
    <input name="codigo" value="{{ old('codigo', $e->codigo ?? '') }}" placeholder="M001" {{ ($e && $e->codigo_confirmado) ? 'readonly' : '' }}
           class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm {{ ($e && $e->codigo_confirmado) ? 'bg-gray-100' : '' }}">
    @if($e && $e->codigo_confirmado)<p class="mt-1 text-xs text-gray-500">Confirmado: no se edita. Desactívelo y cree otro.</p>@endif
</div>
<div>
    <label class="mb-1 block text-sm font-medium text-gray-700">Tipo (CAT-009)</label>
    <select name="tipo_establecimiento" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        @foreach($tiposEstablecimiento as $t)
            <option value="{{ $t->codigo }}" @selected(old('tipo_establecimiento', $e->tipo_establecimiento ?? '02') === (string) $t->codigo)>
                {{ $t->codigo }} - {{ $t->descripcion }}{{ isset($letras[$t->codigo]) ? ' (letra '.$letras[$t->codigo].')' : '' }}
            </option>
        @endforeach
    </select>
</div>
<div>
    <label class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
    <input name="nombre" value="{{ old('nombre', $e->nombre ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
</div>

@include('dte::partials.ubicacion-select', [
    'prefix' => $uid,
    'dep' => old('departamento', $e->departamento ?? ''),
    'mun' => old('municipio', $e->municipio ?? ''),
    'dis' => old('distrito', $e->distrito ?? ''),
    'departamentos' => $departamentos, 'municipios' => $municipios, 'distritos' => $distritos,
])

<div class="md:col-span-3">
    <label class="mb-1 block text-sm font-medium text-gray-700">Dirección (complemento; incluya el nombre del distrito)</label>
    <input name="direccion" value="{{ old('direccion', $e->direccion ?? '') }}" maxlength="200" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
</div>
<div>
    <label class="mb-1 block text-sm font-medium text-gray-700">Teléfono (8 a 30 caracteres)</label>
    <input name="telefono" value="{{ old('telefono', $e->telefono ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
</div>
<div>
    <label class="mb-1 block text-sm font-medium text-gray-700">Correo</label>
    <input name="correo" type="email" value="{{ old('correo', $e->correo ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
</div>
<div class="flex items-end gap-6">
    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="codigo_confirmado" value="1" @checked(old('codigo_confirmado', $e->codigo_confirmado ?? false))> Código confirmado por Hacienda</label>
    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="activo" value="1" @checked(old('activo', $e->activo ?? true))> Activo</label>
</div>
