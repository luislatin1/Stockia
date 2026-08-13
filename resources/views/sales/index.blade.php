@extends('layouts.app')

@section('title', 'Ventas')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Ventas</h2>
        @if($view === 'live')
            <div class="flex items-center gap-2">
                <a href="{{ route('sales.export.excel') }}" class="rounded border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                    Exportar Excel
                </a>
                <a href="{{ route('ptvpos.pos') }}" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Ir a Punto de Venta
                </a>
                <button type="button" disabled title="Deshabilitado: las ventas deben registrarse desde POS." class="cursor-not-allowed rounded border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-500">
                    + Nueva Venta (deshabilitado)
                </button>
            </div>
        @endif
    </div>

    <div class="flex items-center gap-2 border-b border-gray-200">
        <a href="{{ route('sales.index') }}"
           class="px-3 py-2 text-sm font-medium border-b-2 {{ $view === 'live' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Nuevas
        </a>
        <a href="{{ route('sales.index', ['view' => 'historico']) }}"
           class="px-3 py-2 text-sm font-medium border-b-2 {{ $view === 'historico' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Histórico (pos)
        </a>
    </div>

    @if($view === 'historico')
        <p class="text-xs text-gray-400">Ventas importadas del sistema anterior — solo consulta, no se pueden editar.</p>

        @forelse($sales as $sale)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-2 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $sale->tipo ?: 'Venta' }} {{ $sale->numero_doc ? '#' . $sale->numero_doc : '' }}</p>
                        <p class="text-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($sale->fecha)->format('d/m/Y') }}</p>
                        <p class="text-xs text-gray-500">Cliente: {{ $sale->cliente_nombre ?: 'N/A' }}</p>
                    </div>
                    @if($sale->anulada)
                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Anulada</span>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                    <p><span class="text-gray-500">Subtotal:</span> ${{ number_format($sale->subtotal, 2) }}</p>
                    <p><span class="text-gray-500">IVA:</span> ${{ number_format($sale->iva, 2) }}</p>
                    <p><span class="text-gray-500">Descuento:</span> ${{ number_format($sale->descuento, 2) }}</p>
                    <p><span class="text-gray-500">Total:</span> ${{ number_format($sale->total, 2) }}</p>
                </div>
            </div>
        @empty
            <p class="rounded border border-dashed border-gray-300 bg-white p-6 text-center text-gray-500">No hay ventas históricas.</p>
        @endforelse

        {{ $sales->links() }}
    @else
        @forelse($sales as $sale)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="mb-2 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">Venta #{{ $sale->id }}</p>
                        <p class="text-xs text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
                        <p class="text-xs text-gray-500">Vendedor: {{ $sale->user->name ?? 'N/A' }}</p>
                    </div>
                    <a href="{{ route('sales.show', $sale) }}" class="text-sm font-semibold text-indigo-600">
                        Ver Detalles
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                    <p><span class="text-gray-500">Subtotal:</span> ${{ number_format($sale->subtotal ?? $sale->total, 2) }}</p>
                    <p><span class="text-gray-500">IVA:</span> ${{ number_format($sale->tax_total ?? 0, 2) }}</p>
                    <p><span class="text-gray-500">Total:</span> ${{ number_format($sale->total, 2) }}</p>
                    <p><span class="text-gray-500">Estado:</span> {{ ucfirst($sale->status) }}</p>
                </div>
            </div>
        @empty
            <p class="rounded border border-dashed border-gray-300 bg-white p-6 text-center text-gray-500">No hay ventas aún.</p>
        @endforelse

        {{ $sales->links() }}
    @endif
</div>
@endsection
