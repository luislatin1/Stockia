@extends('layouts.app')

@section('title', 'Wizard de Módulo')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Instalador de Módulos</h2>
        <p class="text-sm text-gray-500">Módulo: <strong>{{ $moduleInfo['name'] }}</strong></p>
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

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider">
            <span class="{{ $step >= 1 ? 'text-indigo-700' : 'text-gray-400' }}">1. Selección</span>
            <span class="text-gray-300">/</span>
            <span class="{{ $step >= 2 ? 'text-indigo-700' : 'text-gray-400' }}">2. Dependencias</span>
            <span class="text-gray-300">/</span>
            <span class="{{ $step >= 3 ? 'text-indigo-700' : 'text-gray-400' }}">3. Instalación</span>
        </div>

        @if ($step === 1)
            <h3 class="text-lg font-semibold text-gray-900">Paso 1: Confirmar módulo</h3>
            <p class="mt-2 text-sm text-gray-600">{{ $moduleInfo['description'] }}</p>

            <a href="{{ route('settings.modules.wizard', ['module' => $key, 'step' => 2]) }}"
               class="mt-5 inline-flex rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Continuar
            </a>
        @elseif ($step === 2)
            <h3 class="text-lg font-semibold text-gray-900">Paso 2: Validar dependencias</h3>
            @if (count($moduleInfo['dependencies']) === 0)
                <p class="mt-2 text-sm text-emerald-700">Este módulo no requiere dependencias.</p>
            @else
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($moduleInfo['dependencies'] as $dependency)
                        @php $dependencyEnabled = (bool) ($installedModules[$dependency] ?? false); @endphp
                        <li class="flex items-center justify-between rounded border p-2">
                            <span>{{ $dependency }}</span>
                            <span class="{{ $dependencyEnabled ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $dependencyEnabled ? 'OK' : 'Pendiente' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <a href="{{ route('settings.modules.wizard', ['module' => $key, 'step' => 3]) }}"
               class="mt-5 inline-flex rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Continuar
            </a>
        @else
            <h3 class="text-lg font-semibold text-gray-900">Paso 3: Instalar/Habilitar</h3>
            <p class="mt-2 text-sm text-gray-600">Se registrará el módulo y quedará activo.</p>

            <form method="POST" action="{{ route('settings.modules.install') }}" class="mt-5">
                @csrf
                <input type="hidden" name="module" value="{{ $key }}">
                <button class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Instalar módulo
                </button>
            </form>
        @endif
    </div>

    <a href="{{ route('settings.modules.index') }}" class="inline-flex rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
        Volver
    </a>
</div>
@endsection

