@extends('layouts.app')

@section('title', 'Abrir Caja')

@section('content')
<div class="max-w-lg space-y-4">
    <h2 class="text-2xl font-bold text-gray-900">Abrir Caja</h2>

    @if($activeSession)
        <div class="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
            Ya tienes una caja abierta:
            <strong>{{ $activeSession->register_name ?? 'Sin nombre' }}</strong>
            ({{ $activeSession->register_code ?? 'N/A' }}).
            Debes cerrarla antes de abrir otra.
        </div>
    @endif

    <form method="POST" action="{{ route('ptvpos.open.store') }}" class="space-y-3">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">Caja</label>
            <select name="register_id" class="w-full rounded border border-gray-300 px-3 py-2">
                <option value="">Selecciona una caja</option>
                @foreach($registers as $register)
                    <option value="{{ $register->id }}" @selected(old('register_id') == $register->id)>
                        {{ $register->name }} ({{ $register->code }})
                    </option>
                @endforeach
            </select>
            @error('register_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Efectivo inicial</label>
            <input type="number" step="0.01" min="0" name="opening_cash" value="{{ old('opening_cash') }}" class="w-full rounded border border-gray-300 px-3 py-2">
        </div>
        <button class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Guardar</button>
    </form>
</div>
@endsection
