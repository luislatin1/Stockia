@extends('layouts.app')

@section('title', 'Nueva Cuenta Bancaria')

@section('content')
<div class="max-w-md mx-auto space-y-6">
    <h2 class="text-2xl font-bold text-gray-900">Nueva Cuenta Bancaria</h2>

    <form action="{{ route('cuentas.store') }}" method="POST" class="rounded-xl border bg-white p-6 shadow-sm space-y-4">
        @csrf
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Banco</label>
            <select name="bank_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @forelse ($banks as $bank)
                    <option value="{{ $bank->id }}" @selected((string) old('bank_id') === (string) $bank->id)>{{ $bank->nombre }}</option>
                @empty
                    <option value="">No hay bancos — crea uno primero</option>
                @endforelse
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">No. de cuenta</label>
            <input type="text" name="numero_cuenta" value="{{ old('numero_cuenta') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Tipo de cuenta</label>
            <select name="tipo_cuenta" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="corriente" @selected(old('tipo_cuenta') === 'corriente')>Corriente</option>
                <option value="ahorro" @selected(old('tipo_cuenta') === 'ahorro')>Ahorro</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Moneda</label>
            <select name="currency_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}" @selected((string) old('currency_id') === (string) $currency->id)>{{ $currency->code }} — {{ $currency->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Guardar</button>
            <a href="{{ route('cuentas.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
        </div>
    </form>
</div>
@endsection
