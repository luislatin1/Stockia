@extends('layouts.app')

@section('title', 'Proveedores')

@section('topbar-actions')
    <a href="{{ route('providers.create') }}"
       class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
        ＋ Nuevo Proveedor
    </a>
@endsection

@section('content')

<x-table>

    <x-slot name="thead">
        <th class="px-4 py-3">Nombre</th>
        <th class="px-4 py-3">NIT</th>
        <th class="px-4 py-3">Teléfono</th>
        <th class="px-4 py-3">Correo</th>
        <th class="px-4 py-3 text-right">Productos</th>
        <th class="px-4 py-3 text-right">Acciones</th>
    </x-slot>

    @forelse($providers as $provider)
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-medium text-gray-900">{{ $provider->nombre }}</td>
            <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $provider->nit ?: '—' }}</td>
            <td class="px-4 py-3 text-gray-500">{{ $provider->telefono ?: '—' }}</td>
            <td class="px-4 py-3 text-gray-500">{{ $provider->email ?: '—' }}</td>
            <td class="px-4 py-3 text-right text-gray-900">{{ $provider->products_count }}</td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('providers.edit', $provider) }}"
                       class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Editar
                    </a>
                    <form action="{{ route('providers.destroy', $provider) }}" method="POST"
                          onsubmit="return confirm('¿Eliminar este proveedor? Los productos que lo usan quedarán sin proveedor.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                            Eliminar
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">
                No hay proveedores registrados.
            </td>
        </tr>
    @endforelse

    <x-slot name="footer">
        {{ $providers->links() }}
    </x-slot>

</x-table>

@endsection
