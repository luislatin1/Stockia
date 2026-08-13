@extends('layouts.app')

@section('title', 'Garantías')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Garantías</h2>
        <p class="text-sm text-gray-500">Garantías generadas automáticamente al vender productos con garantía configurada.</p>
    </div>

    <div class="flex gap-2 text-sm">
        <a href="{{ route('warranties.index') }}" class="rounded-lg border px-3 py-1.5 {{ ! request('estado') ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 text-gray-700' }}">Todas</a>
        <a href="{{ route('warranties.index', ['estado' => 'vigente']) }}" class="rounded-lg border px-3 py-1.5 {{ request('estado') === 'vigente' ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 text-gray-700' }}">Vigentes</a>
        <a href="{{ route('warranties.index', ['estado' => 'reclamada']) }}" class="rounded-lg border px-3 py-1.5 {{ request('estado') === 'reclamada' ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 text-gray-700' }}">Reclamadas</a>
        <a href="{{ route('warranties.index', ['estado' => 'resuelta']) }}" class="rounded-lg border px-3 py-1.5 {{ request('estado') === 'resuelta' ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 text-gray-700' }}">Resueltas</a>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Producto</th>
                    <th class="px-4 py-3 text-left">Cliente</th>
                    <th class="px-4 py-3 text-left">Vence</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($warranties as $warranty)
                    <tr>
                        <td class="px-4 py-3">{{ $warranty->product->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $warranty->customer->nombre ?? '—' }}</td>
                        <td class="px-4 py-3 {{ $warranty->esta_vencida ? 'text-red-600 font-medium' : '' }}">
                            {{ $warranty->fecha_vencimiento->format('d/m/Y') }}
                            @if ($warranty->esta_vencida)
                                <span class="text-xs">(vencida)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 capitalize">{{ $warranty->estado }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('warranties.show', $warranty) }}" class="text-indigo-600 hover:underline text-sm">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay garantías registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $warranties->links() }}
</div>
@endsection
