@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Detalle de Usuario</h2>
            <p class="text-sm text-gray-500">Información general y permisos del usuario.</p>
        </div>
        <a href="{{ route('users.edit', $user->id) }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
            Editar
        </a>
    </div>

    @php
        $role = optional($user->companies->first())->pivot->role;
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-lg bg-gray-50 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">ID</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->id }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nombre</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->name }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-4 sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Email</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->email }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rol</dt>
                <dd class="mt-2">
                    <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                        {{ $role }}
                    </span>
                </dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Registro</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ optional($user->created_at)->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>

    <a href="{{ route('users.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
        Volver al listado
    </a>
</div>
@endsection
