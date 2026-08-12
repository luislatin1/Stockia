@extends('layouts.app')

@section('title', 'Categorías')

@section('topbar-actions')
    <a href="{{ route('categories.create') }}"
       class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
        ＋ Nueva Categoría
    </a>
@endsection

@section('content')

<x-table>

    <x-slot name="thead">
        <th class="px-4 py-3">Nombre</th>
        <th class="px-4 py-3">Descripción</th>
        <th class="px-4 py-3 text-right">Productos</th>
        <th class="px-4 py-3 text-right">Acciones</th>
    </x-slot>

    @forelse($categories as $category)
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-medium text-gray-900">{{ $category->nombre }}</td>
            <td class="px-4 py-3 text-gray-500">{{ $category->descripcion ?: '—' }}</td>
            <td class="px-4 py-3 text-right text-gray-900">{{ $category->products_count }}</td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('categories.edit', $category) }}"
                       class="rounded-md border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Editar
                    </a>
                    <form action="{{ route('categories.destroy', $category) }}" method="POST"
                          onsubmit="return confirm('¿Eliminar esta categoría? Los productos que la usan quedarán sin categoría.');">
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
            <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-400">
                No hay categorías registradas.
            </td>
        </tr>
    @endforelse

    <x-slot name="footer">
        {{ $categories->links() }}
    </x-slot>

</x-table>

@endsection
