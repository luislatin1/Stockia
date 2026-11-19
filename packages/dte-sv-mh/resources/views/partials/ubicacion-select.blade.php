{{--
    Selectores en cascada departamento → municipio → distrito.
    Variables: $prefix (id único), $dep, $mun, $dis (valores actuales),
               $departamentos, $municipios, $distritos.
    Los distritos se filtran por departamento; cuando se conozca el municipio de
    cada distrito (dte_distritos.municipio_codigo) también se filtran por municipio.
--}}
@php($uid = $prefix ?? 'ub'.uniqid())
<div class="contents" data-ubicacion="{{ $uid }}">
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Departamento</label>
        <select name="departamento" data-role="dep" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Seleccionar</option>
            @foreach($departamentos as $d)
                <option value="{{ $d->codigo }}" @selected((string) $dep === (string) $d->codigo)>{{ $d->codigo }} - {{ $d->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Municipio</label>
        <select name="municipio" data-role="mun" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Seleccionar</option>
            @foreach($municipios as $m)
                <option value="{{ $m->codigo }}" data-dep="{{ $m->departamento_codigo }}" @selected((string) $mun === (string) $m->codigo)>{{ $m->codigo_local }} - {{ $m->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Distrito</label>
        <select name="distrito" data-role="dis" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Seleccionar</option>
            @foreach($distritos as $x)
                <option value="{{ $x->codigo }}" data-dep="{{ $x->departamento_codigo }}" data-mun="{{ $x->municipio_codigo }}" @selected((string) $dis === (string) $x->codigo && (string) $dep === (string) $x->departamento_codigo)>{{ $x->codigo }} - {{ $x->nombre }}</option>
            @endforeach
        </select>
    </div>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-ubicacion]').forEach(function (box) {
        const dep = box.querySelector('[data-role=dep]');
        const mun = box.querySelector('[data-role=mun]');
        const dis = box.querySelector('[data-role=dis]');
        if (!dep || !mun || !dis) return;

        const filter = (select, keep) => {
            Array.from(select.options).forEach(function (o) {
                if (!o.value) return;
                o.hidden = !keep(o);
            });
            if (select.selectedOptions.length && select.selectedOptions[0].hidden) select.value = '';
        };

        const apply = function () {
            filter(mun, (o) => !dep.value || o.dataset.dep === dep.value);
            filter(dis, (o) => (!dep.value || o.dataset.dep === dep.value)
                && (!mun.value || !o.dataset.mun || o.dataset.mun === mun.value));
        };

        dep.addEventListener('change', apply);
        mun.addEventListener('change', apply);
        apply();
    });
});
</script>
@endonce
