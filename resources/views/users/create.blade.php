@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Nuevo Usuario</h2>
        <p class="text-sm text-gray-500">Crea un usuario y asigna su rol inicial.</p>
    </div>

    <form action="{{ route('users.store') }}" method="POST" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-4">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Nombre completo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="correo@dominio.com" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Contraseña</label>
                <input id="password" type="password" name="password" placeholder="Mínimo 6 caracteres" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            </div>

            <div>
                <label for="role" class="mb-1 block text-sm font-medium text-gray-700">Rol</label>
                <select id="role" name="role" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>
                            {{ $role }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Crear Usuario
            </button>
            <a href="{{ route('users.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Cancelar
            </a>
        </div>
    </form>
</div>

@endsection
