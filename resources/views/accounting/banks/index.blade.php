@extends('layouts.app')

@section('title', 'Bancos')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Bancos</h2>
            <p class="text-sm text-gray-500">Catálogo de bancos donde la empresa tiene cuentas.</p>
        </div>
        <a href="{{ route('bancos.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Nuevo banco
        </a>
    </div>

    @if (session('success'))
        <x-stockia.alert tone="success">{{ session('success') }}</x-stockia.alert>
    @endif
    @if ($errors->any())
        <x-stockia.alert tone="danger">{{ $errors->first() }}</x-stockia.alert>
    @endif

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Banco</th>
                    <th class="px-4 py-3 text-right">Cuentas</th>
                    <th class="px-4 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($banks as $bank)
                    <tr>
                        <td class="px-4 py-3 text-gray-900">{{ $bank->nombre }}</td>
                        <td class="px-4 py-3 text-right">{{ $bank->accounts_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <form action="{{ route('bancos.destroy', $bank) }}" method="POST" onsubmit="return confirm('¿Eliminar este banco?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-500">No hay bancos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ route('cuentas.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Ver Cuentas Bancarias →</a>
</div>
@endsection
