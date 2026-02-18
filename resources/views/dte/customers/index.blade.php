@extends('layouts.app')

@section('title', 'Clientes DTE')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Clientes DTE</h1>
            <p class="text-sm text-gray-500">Gestiona receptores para emisión local/dummy de documentos.</p>
        </div>
        <a href="{{ route('dte.customers.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            + Nuevo Cliente
        </a>
    </div>

    <form method="GET" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row">
            <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por nombre, documento o NRC" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Buscar</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Documento</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">NRC</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Contribuyente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $customer->nombre }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $customer->tipo_documento }} - {{ $customer->numero_documento }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $customer->nrc ?: 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($customer->es_contribuyente)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Sí</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('dte.customers.show', $customer) }}" class="rounded-md border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700">Ver</a>
                                    <a href="{{ route('dte.customers.edit', $customer) }}" class="rounded-md border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">Editar</a>
                                    <form action="{{ route('dte.customers.destroy', $customer) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('¿Eliminar cliente DTE?')" class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No hay clientes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $customers->links() }}
    </div>
</div>
@endsection
