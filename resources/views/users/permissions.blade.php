@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Permisos de {{ $user->name }}</h2>
        <p class="text-sm text-gray-500">
            Rol actual: <span class="font-semibold">{{ $companyUser->role }}</span>.
            Por defecto cada pantalla usa el rol; aquí puedes forzar una excepción por pantalla para este usuario.
        </p>
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

    <form action="{{ route('users.permissions.update', $user->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PATCH')

        @foreach ($sections as $sectionName => $items)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ $sectionName }}</h3>
                <div class="divide-y divide-gray-100">
                    @foreach ($items as $item)
                        <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $item['label'] }}</p>
                                <p class="text-xs text-gray-500">
                                    Por rol ({{ $companyUser->role }}):
                                    {{ $item['inherited_allowed'] ? 'permitido' : 'no permitido' }}
                                </p>
                            </div>
                            <div class="flex gap-3 text-sm">
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="radio" name="permissions[{{ $item['key'] }}]" value="heredado"
                                           {{ $item['state'] === 'heredado' ? 'checked' : '' }}
                                           class="text-indigo-600 focus:ring-indigo-500">
                                    Heredado
                                </label>
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="radio" name="permissions[{{ $item['key'] }}]" value="forzado_si"
                                           {{ $item['state'] === 'forzado_si' ? 'checked' : '' }}
                                           class="text-emerald-600 focus:ring-emerald-500">
                                    Forzar Sí
                                </label>
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="radio" name="permissions[{{ $item['key'] }}]" value="forzado_no"
                                           {{ $item['state'] === 'forzado_no' ? 'checked' : '' }}
                                           class="text-rose-600 focus:ring-rose-500">
                                    Forzar No
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex flex-wrap items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Guardar permisos
            </button>
            <a href="{{ route('users.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
