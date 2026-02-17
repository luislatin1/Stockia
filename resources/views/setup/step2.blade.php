@extends('layouts.app')

@section('title', 'Configuración Inicial')

@section('content')
<div class="max-w-2xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Paso 2: Primer almacén</h2>
        <p class="text-sm text-gray-500">Crea el primer almacén para operar.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('setup.step2.store') }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">Nombre</label>
            <input name="name" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('name', 'Principal') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Código</label>
            <input name="code" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('code', 'MAIN-1') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Ubicación</label>
            <input name="location" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" value="{{ old('location') }}">
        </div>
        <button class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Finalizar</button>
    </form>
</div>
@endsection

