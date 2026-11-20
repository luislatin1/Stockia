@extends('layouts.app')

@section('title', 'Nota de Débito')

@section('content')
@php
    $isAdminRole = in_array(function_exists('currentRole') ? currentRole() : null, ['Admin', 'SuperAdmin'], true);
@endphp
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Nota de Débito</h2>
        <p class="text-sm text-gray-500">Venta #{{ $sale->id }} | CCF {{ $origen->numero_control }}</p>
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

    <div class="rounded border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900 space-y-1">
        <p>La nota de débito <strong>aumenta</strong> el valor de un Comprobante de Crédito Fiscal ya aceptado por Hacienda: intereses, gastos o una diferencia de precio a favor del vendedor.</p>
        <p>Si la venta fue a crédito, el total de la nota (con IVA) se suma al saldo pendiente del cliente. Si fue de contado, el cobro del cargo se registra aparte.</p>
        <p class="text-xs">Los montos se ingresan <strong>sin IVA</strong>; el IVA se calcula al emitir. No mueve inventario.</p>
    </div>

    <form method="POST" action="{{ route('sales.debit-note.store', $sale) }}" class="space-y-4">
        @csrf

        <div class="rounded border border-gray-200 bg-white p-4 space-y-3">
            <label class="block text-sm font-medium text-gray-700">Motivo</label>
            <select name="motivo" class="w-full rounded border border-gray-300 px-3 py-2">
                <option value="">Seleccionar</option>
                @foreach($motivos as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(old('motivo') === $clave)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>

        <div class="rounded border border-gray-200 bg-white p-4 space-y-3">
            <h3 class="font-semibold text-gray-900">Cargos</h3>
            @foreach(range(0, 2) as $i)
                <div class="grid grid-cols-1 gap-2 md:grid-cols-6">
                    <input name="lines[{{ $i }}][descripcion]" value="{{ old('lines.'.$i.'.descripcion') }}" maxlength="200" placeholder="Descripción del cargo" class="md:col-span-3 rounded border border-gray-300 px-3 py-2">
                    <input type="number" step="0.01" min="0" name="lines[{{ $i }}][monto]" value="{{ old('lines.'.$i.'.monto') }}" placeholder="Monto sin IVA" class="md:col-span-2 rounded border border-gray-300 px-3 py-2 text-right">
                    <select name="lines[{{ $i }}][categoria]" class="rounded border border-gray-300 px-2 py-2">
                        <option value="gravada" @selected(old('lines.'.$i.'.categoria', 'gravada') === 'gravada')>Gravada</option>
                        <option value="exenta" @selected(old('lines.'.$i.'.categoria') === 'exenta')>Exenta</option>
                        <option value="no_sujeta" @selected(old('lines.'.$i.'.categoria') === 'no_sujeta')>No sujeta</option>
                    </select>
                </div>
            @endforeach
            <p class="text-xs text-gray-500">Deje en blanco las filas que no use.</p>
        </div>

        <div class="rounded border border-gray-200 bg-white p-4">
            <label class="block text-sm font-medium text-gray-700">Notas administrativas</label>
            <textarea name="notes" rows="3" maxlength="500" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">{{ old('notes') }}</textarea>
        </div>

        @if(! $isAdminRole)
            <div class="rounded border border-amber-200 bg-amber-50 p-4">
                <label class="block text-sm font-medium text-amber-900">Clave de administrador</label>
                <input type="password" name="admin_password" autocomplete="off" class="mt-1 w-full rounded border border-amber-300 bg-white px-3 py-2">
            </div>
        @endif

        <div class="flex items-center gap-2">
            <a href="{{ route('sales.show', $sale) }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Volver</a>
            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Emitir nota de débito</button>
        </div>
    </form>
</div>
@endsection
