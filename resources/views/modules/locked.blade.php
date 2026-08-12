@extends('layouts.app')

@section('title', 'Módulo no disponible')

@section('content')

<div class="max-w-2xl">
    <x-stockia.alert tone="warning">
        <p class="font-semibold">
            La función “{{ \App\Models\Module::CATALOG[$key]['name'] ?? $key }}” no está disponible.
        </p>
        <p class="mt-1">
            {{ \App\Models\Module::CATALOG[$key]['description'] ?? '' }}
        </p>
        @if (currentRole() === 'SuperAdmin')
            <a href="{{ route('settings.modules.index') }}"
               class="mt-3 inline-block rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 transition-colors">
                Activar módulo en Ajustes
            </a>
        @else
            <p class="mt-2 text-sm">Pide a un administrador que active este módulo en Ajustes &gt; Módulos.</p>
        @endif
    </x-stockia.alert>
</div>

@endsection
