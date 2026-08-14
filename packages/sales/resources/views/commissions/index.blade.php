@extends('layouts.app')

@section('title', 'Comisiones')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Comisiones</h2>
        <p class="text-sm text-gray-500">Resumen de comisiones generadas y pagadas por vendedor.</p>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Vendedor</th>
                    <th class="px-4 py-3 text-right">Generado</th>
                    <th class="px-4 py-3 text-right">Pagado</th>
                    <th class="px-4 py-3 text-right">Pendiente</th>
                    <th class="px-4 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($vendedores as $vendedor)
                    <tr>
                        <td class="px-4 py-3 text-gray-900">{{ $vendedor->name }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format($vendedor->generado ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-right text-emerald-600">${{ number_format($vendedor->pagado ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-amber-600">${{ number_format(($vendedor->generado ?? 0) - ($vendedor->pagado ?? 0), 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('commissions.show', $vendedor) }}" class="text-indigo-600 hover:underline text-sm">Ver detalle</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay vendedores con comisión configurada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
