@extends('layouts.app')

@section('title', 'Comisiones — ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
        <p class="text-sm text-gray-500">Historial de comisiones por venta.</p>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Venta</th>
                    <th class="px-4 py-3 text-left">Fecha</th>
                    <th class="px-4 py-3 text-right">Base</th>
                    <th class="px-4 py-3 text-right">%</th>
                    <th class="px-4 py-3 text-right">Monto</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($commissions as $commission)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('sales.show', $commission->sale_id) }}" class="text-indigo-600 hover:underline">
                                #{{ $commission->sale->numero_interno ?? $commission->sale_id }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $commission->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format($commission->base_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($commission->rate, 2) }}%</td>
                        <td class="px-4 py-3 text-right font-semibold">${{ number_format($commission->amount, 2) }}</td>
                        <td class="px-4 py-3">
                            @if ($commission->status === 'pagada')
                                <span class="text-emerald-600">Pagada</span>
                            @else
                                <span class="text-amber-600">Generada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($commission->status !== 'pagada')
                                <form action="{{ route('commissions.pay', $commission) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-indigo-600 hover:underline text-sm">Marcar pagada</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">Este vendedor no tiene comisiones registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <a href="{{ route('commissions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver a Comisiones</a>
</div>
@endsection
