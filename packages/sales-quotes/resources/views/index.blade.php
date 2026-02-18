@extends('layouts.app')

@section('title', 'Cotizaciones')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Cotizaciones</h2>
        <a href="{{ route('salesquotes.create') }}" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Nueva Cotización
        </a>
    </div>

    @forelse($quotes as $quote)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-2 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-gray-900">{{ $quote->quote_number ?: ('COT #' . $quote->id) }}</p>
                    <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($quote->created_at)->format('d/m/Y H:i') }}</p>
                    <p class="text-xs text-gray-500">Cliente: {{ $quote->customer_name }}</p>
                    <p class="text-xs text-gray-500">Vendedor: {{ $quote->user_name ?? ('#' . $quote->user_id) }}</p>
                </div>
                <a href="{{ route('salesquotes.show', $quote->id) }}" class="text-sm font-semibold text-indigo-600">
                    Ver Detalles
                </a>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                <p><span class="text-gray-500">Subtotal:</span> ${{ number_format((float) $quote->subtotal, 2) }}</p>
                <p><span class="text-gray-500">IVA:</span> ${{ number_format((float) $quote->tax_total, 2) }}</p>
                <p><span class="text-gray-500">Total:</span> ${{ number_format((float) $quote->total, 2) }}</p>
                <p><span class="text-gray-500">Estado:</span> {{ ucfirst($quote->status) }}</p>
            </div>
        </div>
    @empty
        <p class="rounded border border-dashed border-gray-300 bg-white p-6 text-center text-gray-500">No hay cotizaciones aún.</p>
    @endforelse
</div>
@endsection
