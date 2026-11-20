@extends('layouts.app')

@section('title', 'Invalidar documento')

@section('content')
@php
    $isAdminRole = in_array(function_exists('currentRole') ? currentRole() : null, ['Admin', 'SuperAdmin'], true);
    $nombres = ['05' => 'Nota de Crédito', '06' => 'Nota de Débito', '18' => 'Evento de Retorno'];
    $esDebito = $documento->tipo_dte === '06';
@endphp
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Invalidar {{ $nombres[$documento->tipo_dte] ?? 'documento' }}</h2>
        <p class="text-sm text-gray-500">Venta #{{ $sale->id }} | {{ $documento->numero_control }}</p>
    </div>

    @if ($errors->any())
        <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 space-y-1">
        <p>Invalidar <strong>no borra</strong> el documento: Hacienda lo marca como INVALIDADO y deja de tener efecto fiscal.</p>
        <p>Plazo: hasta el décimo día hábil del mes siguiente al que obtuvo el sello.</p>
        <p><strong>No se modifican inventario ni caja.</strong> Si la devolución física ya se registró y solo estaba mal el monto, vuelva a acreditar con "Devolver / ajustar" usando el motivo "Error de precio" (nota de crédito de valor).</p>
        @if($documento->tipo_dte === '18')
            <p>Al invalidar el Evento de Retorno, la factura vuelve a poder invalidarse o corregirse.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('sales.dte.invalidate.store', [$sale, $documento->id]) }}" class="space-y-4">
        @csrf

        <div class="rounded border border-gray-200 bg-white p-4 space-y-2">
            <h3 class="font-semibold text-gray-900">Tipo de invalidación</h3>
            <label class="flex items-start gap-2"><input type="radio" name="tipo_invalidacion" value="2" class="mt-1" @checked(old('tipo_invalidacion', '2') === '2')>
                <span>Rescindir la operación <span class="text-xs text-gray-500">(el documento no debió existir y no se reemplaza)</span></span></label>
            <label class="flex items-start gap-2"><input type="radio" name="tipo_invalidacion" value="1" class="mt-1" @checked(old('tipo_invalidacion') === '1')>
                <span>Error en la información del documento</span></label>
            <label class="flex items-start gap-2"><input type="radio" name="tipo_invalidacion" value="3" class="mt-1" @checked(old('tipo_invalidacion') === '3')>
                <span>Otro</span></label>
            @if($esDebito)
                <p class="text-xs text-gray-600">Con "error" u "otro", Hacienda exige emitir antes la nota de débito correcta (desde "Nota de débito" en la venta) y elegirla aquí como reemplazo.</p>
            @else
                <p class="text-xs text-gray-600">En {{ $nombres[$documento->tipo_dte] ?? 'este documento' }} no hay documento de reemplazo: si el error era de la nota, emita una nueva después de invalidar.</p>
            @endif
        </div>

        @if($esDebito)
            <div class="rounded border border-gray-200 bg-white p-4">
                <label class="block text-sm font-medium text-gray-700">Nota de débito que lo reemplaza (solo para error u otro)</label>
                <select name="replacement_dte_id" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                    <option value="">Ninguna</option>
                    @foreach($reemplazos as $r)
                        <option value="{{ $r->id }}" @selected((int) old('replacement_dte_id') === $r->id)>{{ $r->numero_control }} &middot; ${{ number_format((float) ($r->json_original['resumen']['totalPagar'] ?? 0), 2) }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="rounded border border-gray-200 bg-white p-4">
            <label class="block text-sm font-medium text-gray-700">Motivo (máx. 180 caracteres)</label>
            <input name="motivo" maxlength="180" value="{{ old('motivo') }}" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
        </div>

        @if(! $isAdminRole)
            <div class="rounded border border-amber-200 bg-amber-50 p-4">
                <label class="block text-sm font-medium text-amber-900">Clave de administrador</label>
                <input type="password" name="admin_password" autocomplete="off" class="mt-1 w-full rounded border border-amber-300 bg-white px-3 py-2">
            </div>
        @endif

        <div class="flex items-center gap-2">
            <a href="{{ route('sales.show', $sale) }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Volver</a>
            <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white">Invalidar ante Hacienda</button>
        </div>
    </form>
</div>
@endsection
