@extends('layouts.app')

@section('title', 'Nuevo Banco')

@section('content')
<div class="max-w-md mx-auto space-y-6">
    <h2 class="text-2xl font-bold text-gray-900">Nuevo Banco</h2>

    <form action="{{ route('bancos.store') }}" method="POST" class="rounded-xl border bg-white p-6 shadow-sm space-y-4">
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
            <label class="mb-1 block text-sm font-medium text-gray-700">Nombre del banco</label>
            <input type="text" name="nombre" value="{{ old('nombre') }}" placeholder="Banco Agrícola"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Guardar</button>
            <a href="{{ route('bancos.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
        </div>
    </form>
</div>
@endsection
