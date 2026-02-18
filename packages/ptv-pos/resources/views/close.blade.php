@extends('layouts.app')

@section('title', 'Cerrar Caja')

@section('content')
<div class="max-w-lg space-y-4">
    <h2 class="text-2xl font-bold text-gray-900">Cerrar Caja</h2>

    @if ($errors->any())
        <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($activeSession)
        <div class="rounded border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
            Caja activa:
            <strong>{{ $activeSession->register_name ?? 'Sin nombre' }}</strong>
            ({{ $activeSession->register_code ?? 'N/A' }}),
            abierta el {{ \Carbon\Carbon::parse($activeSession->opened_at)->format('d/m/Y H:i') }}.
        </div>

        <div class="rounded border border-gray-200 bg-white p-3 text-sm">
            <p class="font-semibold text-gray-800 mb-2">Arqueo esperado</p>
            <div class="space-y-1 text-gray-700">
                <div class="flex justify-between"><span>Base apertura</span><span>${{ number_format((float) $activeSession->opening_cash, 2) }}</span></div>
                <div class="flex justify-between"><span>Ventas del turno</span><span>${{ number_format((float) ($snapshot['sales_total'] ?? 0), 2) }}</span></div>
                <div class="flex justify-between"><span>Ingresos manuales</span><span>${{ number_format((float) ($snapshot['manual_in'] ?? 0), 2) }}</span></div>
                <div class="flex justify-between"><span>Egresos manuales</span><span>${{ number_format((float) ($snapshot['manual_out'] ?? 0), 2) }}</span></div>
                <div class="mt-2 flex justify-between font-semibold text-gray-900 border-t pt-2">
                    <span>Efectivo esperado</span>
                    <span>${{ number_format((float) ($snapshot['expected_cash'] ?? 0), 2) }}</span>
                </div>
            </div>
        </div>
    @else
        <div class="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
            No tienes una caja abierta para cerrar.
        </div>
        @if($isAdminRole ?? false)
            <div class="rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                Vista administrativa: puedes consultar historial de movimientos aunque no tengas caja abierta.
            </div>
        @endif
    @endif

    <form method="POST" action="{{ route('ptvpos.close.store') }}" class="space-y-3">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">Efectivo contado</label>
            <input type="number" step="0.01" min="0" name="closing_cash" value="{{ old('closing_cash') }}" class="w-full rounded border border-gray-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Observación (obligatoria si hay diferencia)</label>
            <input type="text" name="closing_note" value="{{ old('closing_note') }}" maxlength="255" class="w-full rounded border border-gray-300 px-3 py-2">
        </div>
        <button class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white" @disabled(! $activeSession)>Cerrar</button>
    </form>

    @if($activeSession || (($isAdminRole ?? false) && $recentCashMovements->isNotEmpty()))
    <div class="rounded border border-gray-200 bg-white p-3 text-sm">
        <p class="font-semibold text-gray-800 mb-2">Ultimos movimientos manuales</p>
        <div class="space-y-2">
            @forelse($recentCashMovements as $movement)
                <div class="rounded bg-gray-50 p-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="{{ $movement->type === 'in' ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $movement->type === 'in' ? 'Ingreso' : 'Egreso' }}
                        </span>
                        <span class="font-semibold">${{ number_format((float) $movement->amount, 2) }}</span>
                    </div>
                    <p class="text-gray-600">{{ $movement->reason ?: '-' }}</p>
                    <p class="text-gray-500 mt-1">
                        Caja: {{ $movement->register_name ?? 'Sin caja' }} ({{ $movement->register_code ?? 'N/A' }})
                        | Usuario: {{ $movement->user_name ?? ('#' . $movement->user_id) }}
                    </p>
                </div>
            @empty
                <p class="text-xs text-gray-500">No hay movimientos manuales.</p>
            @endforelse
        </div>
    </div>
    @endif
</div>
@endsection
