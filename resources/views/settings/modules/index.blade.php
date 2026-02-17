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

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $modulesTableReady)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-800">
            La tabla de módulos aún no existe. Ejecuta: <strong>php artisan migrate</strong>
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Cargar Plugin (ZIP)</h3>
                <p class="text-sm text-gray-500">Sube un ZIP de plugin para preparar su instalación.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.modules.upload') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 md:flex-row md:items-end">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Archivo ZIP</label>
                <input type="file" name="plugin_zip" accept=".zip" class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="w-full md:w-64">
                <label class="block text-sm font-medium text-gray-700">Carpeta (opcional)</label>
                <input type="text" name="plugin_folder" placeholder="ptv-pos" class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">Nombre de carpeta en `packages/`. No afecta el `module_key`.</p>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="plugin_force" value="1" class="rounded border-gray-300">
                Sobrescribir si existe
            </label>
            <button class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Cargar
            </button>
        </form>

        @if(session('plugin_install'))
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                <div class="font-semibold">Siguiente pasos (CLI):</div>
                <ul class="mt-2 list-disc pl-5 text-sm">
                    @foreach(session('plugin_install.commands') as $cmd)
                        <li><code>{{ $cmd }}</code></li>
                    @endforeach
                </ul>
                <div class="mt-2 text-xs text-emerald-700">
                    Paquete: {{ session('plugin_install.package') }}
                </div>
                <div class="mt-1 text-xs text-emerald-700">
                    Module key: {{ session('plugin_install.module_key') }} {{ session('plugin_install.provider') ? ' | Provider: ' . session('plugin_install.provider') : '' }}
                </div>
            </div>
        @endif
    </div>

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
                    @if (! ($info['provider_exists'] ?? true))
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Provider no cargado</span>
                    @elseif ($installed)
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
