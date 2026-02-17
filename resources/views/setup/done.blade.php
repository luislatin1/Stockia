@extends('layouts.app')

@section('title', 'Configuración Inicial')

@section('content')
<div class="max-w-2xl space-y-4">
    <h2 class="text-2xl font-bold text-gray-900">Configuración completa</h2>
    <p class="text-sm text-gray-500">Tu negocio ya está listo para operar.</p>

    <a href="{{ route('dashboard') }}" class="inline-flex rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">
        Ir al Dashboard
    </a>
</div>
@endsection

