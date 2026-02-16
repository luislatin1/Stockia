@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Usuarios</h1>
            <p class="text-sm text-gray-500">Administra usuarios y sus roles dentro de la empresa actual.</p>
        </div>
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
            + Crear Usuario
        </a>
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

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Rol</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Creado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($users as $user)
                @php
                    $currentRole = optional($user->companies->first())->pivot->role;
                @endphp
                    <tr class="hover:bg-gray-50/70">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $user->id }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">{{ $user->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <form action="{{ route('users.role.update', $user->id) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="role" class="rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role }}" {{ $currentRole === $role ? 'selected' : '' }}>
                                            {{ $role }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                    Guardar
                                </button>
                            </form>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ optional($user->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('users.show', $user->id) }}" class="rounded-md border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:bg-sky-100">Ver</a>
                                <a href="{{ route('users.edit', $user->id) }}" class="rounded-md border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">Editar</a>
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100" onclick="return confirm('¿Seguro?')">Quitar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
