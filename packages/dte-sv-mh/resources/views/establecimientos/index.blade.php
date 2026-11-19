@extends('layouts.app')

@section('title', 'Establecimientos DTE')

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('dte.admin.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Administración DTE</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Establecimientos y puntos de venta</h1>
        <p class="text-sm text-gray-500">
            Definen la 3.ª sección del número de control: <span class="font-mono">DTE-01-<b>M001P001</b>-000000000000001</span>.
            Los códigos los asigna Hacienda; hasta marcarlos como <b>confirmados</b> solo sirven en simulación.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @php($sinConfirmar = $establecimientos->contains(fn ($e) => ! $e->codigo_confirmado || $e->puntosVenta->contains(fn ($p) => ! $p->codigo_confirmado)))
    @if($sinConfirmar)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Hay códigos <b>provisionales</b> (sin confirmar). El modo <b>real</b> no emitirá DTE con ellos.
            Confirme el código asignado por Hacienda para cada establecimiento y punto de venta.
        </div>
    @endif

    @foreach($establecimientos as $e)
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-5 py-3">
                <div>
                    <span class="rounded bg-gray-100 px-2 py-1 font-mono text-sm font-semibold">{{ $e->codigo }}</span>
                    <span class="ml-2 font-semibold text-gray-900">{{ $e->nombre }}</span>
                    @unless($e->activo)<span class="ml-2 text-xs text-gray-500">(inactivo)</span>@endunless
                </div>
                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $e->codigo_confirmado ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $e->codigo_confirmado ? 'Código confirmado' : 'Código provisional' }}
                </span>
            </div>

            <div class="space-y-4 p-5">
                <details>
                    <summary class="cursor-pointer text-sm font-semibold text-indigo-600">Editar establecimiento</summary>
                    <form method="POST" action="{{ route('dte.establecimientos.update', $e) }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        @csrf @method('PATCH')
                        @include('dte::establecimientos.partials.fields', ['e' => $e, 'uid' => 'e'.$e->id])
                        <div class="md:col-span-3"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Guardar</button></div>
                    </form>
                </details>

                <div>
                    <h3 class="mb-2 text-sm font-semibold text-gray-700">Puntos de venta</h3>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <tbody class="divide-y divide-gray-100">
                        @forelse($e->puntosVenta as $pv)
                            <tr>
                                <td colspan="2" class="py-2">
                                    <form method="POST" action="{{ route('dte.puntos-venta.update', $pv) }}" class="flex flex-wrap items-center gap-3">
                                        @csrf @method('PATCH')
                                        <input name="codigo" value="{{ $pv->codigo }}" {{ $pv->codigo_confirmado ? 'readonly' : '' }}
                                               class="w-24 rounded-lg border border-gray-300 px-2 py-1 font-mono text-sm {{ $pv->codigo_confirmado ? 'bg-gray-100' : '' }}">
                                        <input name="nombre" value="{{ $pv->nombre }}" class="w-64 rounded-lg border border-gray-300 px-2 py-1 text-sm">
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="codigo_confirmado" value="1" @checked($pv->codigo_confirmado)> confirmado</label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="activo" value="1" @checked($pv->activo)> activo</label>
                                        <button class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="py-2 text-gray-500">Sin puntos de venta.</td></tr>
                        @endforelse
                        </tbody>
                    </table>

                    <form method="POST" action="{{ route('dte.establecimientos.puntos-venta.store', $e) }}" class="mt-3 flex flex-wrap items-center gap-3">
                        @csrf
                        <input name="codigo" placeholder="P002" class="w-24 rounded-lg border border-gray-300 px-2 py-1 font-mono text-sm">
                        <input name="nombre" placeholder="Nombre del punto de venta" class="w-64 rounded-lg border border-gray-300 px-2 py-1 text-sm">
                        <label class="inline-flex items-center gap-1 text-sm"><input type="checkbox" name="codigo_confirmado" value="1"> confirmado</label>
                        <button class="rounded-lg bg-gray-800 px-3 py-1 text-xs font-semibold text-white hover:bg-gray-900">Agregar punto de venta</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">Nuevo establecimiento</h2>
        <form method="POST" action="{{ route('dte.establecimientos.store') }}" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            @csrf
            @include('dte::establecimientos.partials.fields', ['e' => null, 'uid' => 'nuevo'])
            <div class="md:col-span-3"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Crear establecimiento</button></div>
        </form>
    </div>
</div>
@endsection
