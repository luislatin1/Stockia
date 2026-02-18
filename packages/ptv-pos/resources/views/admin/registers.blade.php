@extends('layouts.app')

@section('title', 'Cajas')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Cajas</h2>
        <p class="text-sm text-gray-500">Administracion de cajas fisicas.</p>
    </div>

    <form method="POST" action="{{ route('ptvpos.admin.registers.store') }}" class="rounded-xl border bg-white p-4 shadow-sm space-y-3 max-w-lg">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">Nombre</label>
            <input name="name" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Codigo</label>
            <input name="code" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
        </div>
        <button class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Crear</button>
    </form>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Nombre</th>
                    <th class="px-4 py-3 text-left">Codigo</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($registers as $register)
                    <tr>
                        <td class="px-4 py-2">{{ $register->name }}</td>
                        <td class="px-4 py-2">{{ $register->code }}</td>
                        <td class="px-4 py-2">{{ $register->is_active ? 'Activo' : 'Inactivo' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

