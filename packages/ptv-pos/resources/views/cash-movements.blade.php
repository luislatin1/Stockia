@extends('layouts.app')

@section('title', 'Movimientos de Caja')

@section('content')
<div class="max-w-5xl space-y-4">
    @if ($errors->any())
        <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <h2 class="text-xl font-bold text-gray-900">Movimientos de Caja</h2>
        @if($activeSession)
            <p class="mt-1 text-sm text-gray-600">
                Caja activa: <span class="font-semibold">{{ $activeSession->register_name ?? 'Sin nombre' }}</span>
                ({{ $activeSession->register_code ?? 'N/A' }})
            </p>
            <p class="mt-1 text-sm text-gray-600">
                Esperado actual: <span class="font-semibold">${{ number_format((float) ($snapshot['expected_cash'] ?? 0), 2) }}</span>
            </p>
        @else
            <p class="mt-1 text-sm text-amber-700">No tienes caja abierta. Abre caja para registrar ingresos/egresos.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('ptvpos.cash-movements.store') }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm space-y-3">
        @csrf
        <p class="text-sm font-semibold text-gray-800">Nuevo movimiento</p>
        <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center gap-2 rounded border p-2">
                <input type="radio" name="type" value="in" @checked(old('type', 'in') === 'in')>
                <span class="text-sm text-emerald-700 font-semibold">Ingreso</span>
            </label>
            <label class="flex items-center gap-2 rounded border p-2">
                <input type="radio" name="type" value="out" @checked(old('type') === 'out')>
                <span class="text-sm text-rose-700 font-semibold">Egreso</span>
            </label>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">Monto</label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Motivo</label>
                <input type="text" name="reason" value="{{ old('reason') }}" required maxlength="255" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
            </div>
        </div>
        <button class="rounded bg-slate-800 px-4 py-2 text-sm font-semibold text-white" @disabled(! $activeSession)>Guardar movimiento</button>
    </form>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-800">Historial de movimientos</h3>
            <form method="GET" action="{{ route('ptvpos.cash-movements.index') }}" class="flex items-center gap-2">
                <select name="type" class="rounded border border-gray-300 px-3 py-2 text-sm">
                    <option value="" @selected($filterType === '')>Todos</option>
                    <option value="in" @selected($filterType === 'in')>Ingresos</option>
                    <option value="out" @selected($filterType === 'out')>Egresos</option>
                </select>
                @if(($isAdminRole ?? false) && $activeSession)
                    <select name="scope" class="rounded border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Caja actual</option>
                        <option value="all" @selected(request('scope') === 'all')>Todo el almacén</option>
                    </select>
                @endif
                <button class="rounded bg-gray-700 px-3 py-2 text-xs font-semibold text-white">Filtrar</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Fecha</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-right">Monto</th>
                            <th class="px-4 py-2 text-left">Caja</th>
                            <th class="px-4 py-2 text-left">Usuario</th>
                            <th class="px-4 py-2 text-left">Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($movements as $movement)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($movement->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 {{ $movement->type === 'in' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $movement->type === 'in' ? 'Ingreso' : 'Egreso' }}
                            </td>
                            <td class="px-4 py-2 text-right font-semibold">${{ number_format((float) $movement->amount, 2) }}</td>
                            <td class="px-4 py-2">{{ $movement->register_name ?? 'Sin caja' }} ({{ $movement->register_code ?? 'N/A' }})</td>
                            <td class="px-4 py-2">{{ $movement->user_name ?? ('#' . $movement->user_id) }}</td>
                            <td class="px-4 py-2">{{ $movement->reason ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500">Sin movimientos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
