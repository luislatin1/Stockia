@extends('layouts.app')

@section('title', 'Garantía #' . $warranty->id)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Garantía — {{ $warranty->product->name ?? '—' }}</h2>
        <p class="text-sm text-gray-500">Venta #{{ $warranty->sale->numero_interno ?? $warranty->sale_id }}</p>
    </div>

    @if (session('success'))
        <x-stockia.alert tone="success">{{ session('success') }}</x-stockia.alert>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Estado</p>
            <p class="mt-1 font-semibold capitalize">{{ $warranty->estado }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Inicio</p>
            <p class="mt-1 font-semibold">{{ $warranty->fecha_inicio->format('d/m/Y') }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Vence</p>
            <p class="mt-1 font-semibold {{ $warranty->esta_vencida ? 'text-red-600' : '' }}">
                {{ $warranty->fecha_vencimiento->format('d/m/Y') }}
                @if ($warranty->esta_vencida) (vencida) @endif
            </p>
        </div>
    </div>

    @if ($warranty->descripcion_falla)
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Falla reportada ({{ optional($warranty->fecha_reclamo)->format('d/m/Y') }})</p>
            <p class="mt-1 text-sm text-gray-800">{{ $warranty->descripcion_falla }}</p>
        </div>
    @endif

    @if ($warranty->resolucion)
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Resolución</p>
            <p class="mt-1 text-sm text-gray-800">{{ $warranty->resolucion }}</p>
        </div>
    @endif

    @if ($warranty->estado === 'vigente')
        <form action="{{ route('warranties.claim', $warranty) }}" method="POST" class="rounded-xl border bg-white p-4 shadow-sm space-y-3">
            @csrf
            <label class="block text-sm font-medium text-gray-700">Registrar reclamo</label>
            <textarea name="descripcion_falla" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Describe la falla reportada por el cliente"></textarea>
            @error('descripcion_falla')
                <p class="text-red-500 text-xs">{{ $message }}</p>
            @enderror
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Registrar reclamo</button>
        </form>
    @elseif ($warranty->estado === 'reclamada')
        <form action="{{ route('warranties.resolve', $warranty) }}" method="POST" class="rounded-xl border bg-white p-4 shadow-sm space-y-3">
            @csrf
            <label class="block text-sm font-medium text-gray-700">Registrar resolución</label>
            <textarea name="resolucion" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Describe cómo se resolvió el reclamo"></textarea>
            @error('resolucion')
                <p class="text-red-500 text-xs">{{ $message }}</p>
            @enderror
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Marcar resuelta</button>
        </form>
    @endif

    <a href="{{ route('warranties.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver a Garantías</a>
</div>
@endsection
