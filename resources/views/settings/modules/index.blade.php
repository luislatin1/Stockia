@extends('layouts.app')

@section('title', 'Panel de Control')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Módulos del Sistema</h2>
            <p class="text-sm text-gray-500">Instala, activa o desactiva capacidades del sistema.</p>
        </div>
    </div>

    @if (! $modulesTableReady)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-800">
            La tabla de módulos aún no existe. Ejecuta: <strong>php artisan migrate</strong>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ($catalog as $key => $info)
            @php
                $installed = $installedModules->get($key);
            @endphp
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $info['name'] }}</h3>
                        <p class="text-sm text-gray-500">{{ $info['description'] }}</p>
                        <p class="mt-1 text-xs text-gray-400">Versión catálogo: {{ $info['version'] }}</p>
                    </div>
                    @if ($installed)
                        @if($installed->enabled)
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Activo</span>
                        @else
                            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">Inactivo</span>
                        @endif
                    @else
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">No instalado</span>
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('settings.modules.wizard', ['module' => $key, 'step' => 1]) }}"
                       class="rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        Abrir Wizard
                    </a>

                    @if ($installed)
                        <form method="POST" action="{{ route('settings.modules.toggle', $installed) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="enabled" value="{{ $installed->enabled ? 0 : 1 }}">
                            <button class="rounded border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                {{ $installed->enabled ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

