@extends('layouts.app')

@section('title', 'Compra #' . $purchase->id)

@section('content')

<div class="max-w-3xl space-y-6">

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide">Fecha</p>
                <p class="font-medium text-gray-900">{{ \Illuminate\Support\Carbon::parse($purchase->fecha)->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide">Proveedor</p>
                <p class="font-medium text-gray-900">{{ $purchase->provider->nombre ?? '—' }}</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase tracking-wide">No. Documento</p>
                <p class="font-medium text-gray-900">{{ $purchase->numero_doc ?: '—' }}</p>
            </div>
        </div>
        @if($purchase->notes)
            <p class="mt-4 text-sm text-gray-600">{{ $purchase->notes }}</p>
        @endif
    </div>

    <x-table>
        <x-slot name="thead">
            <th class="px-4 py-3">Producto</th>
            <th class="px-4 py-3 text-right">Cantidad</th>
            <th class="px-4 py-3 text-right">Costo unitario</th>
            <th class="px-4 py-3 text-right">Subtotal</th>
        </x-slot>

        @foreach ($purchase->items as $item)
            <tr>
                <td class="px-4 py-3 text-gray-900">{{ $item->product->name ?? '—' }}</td>
                <td class="px-4 py-3 text-right">{{ $item->cantidad }}</td>
                <td class="px-4 py-3 text-right">${{ number_format($item->costo_unitario, 2) }}</td>
                <td class="px-4 py-3 text-right font-medium">${{ number_format($item->subtotal, 2) }}</td>
            </tr>
        @endforeach

        <x-slot name="footer">
            <div class="flex flex-col items-end gap-1 text-sm text-gray-700">
                <div>Gravadas: ${{ number_format($purchase->gravadas, 2) }}</div>
                <div>Exentas: ${{ number_format($purchase->exentas, 2) }}</div>
                <div>IVA (13%): ${{ number_format($purchase->iva, 2) }}</div>
                <div class="font-semibold text-gray-900">Total: ${{ number_format($purchase->total, 2) }}</div>
            </div>
        </x-slot>
    </x-table>

    @if ($purchase->es_credito)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-xs uppercase tracking-wide">Saldo pendiente</p>
                    <p class="text-xl font-bold {{ $purchase->saldo_pendiente > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                        ${{ number_format($purchase->saldo_pendiente, 2) }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-gray-400 text-xs uppercase tracking-wide">Vence</p>
                    <p class="font-medium {{ $purchase->esta_vencida ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $purchase->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                        @if ($purchase->esta_vencida)
                            <span class="text-xs">(vencida)</span>
                        @endif
                    </p>
                </div>
            </div>

            @if ($purchase->payments->isNotEmpty())
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                            <th class="text-left py-1">Fecha</th>
                            <th class="text-left py-1">Forma</th>
                            <th class="text-left py-1">Referencia</th>
                            <th class="text-right py-1">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($purchase->payments as $payment)
                            <tr>
                                <td class="py-1.5">{{ $payment->fecha->format('d/m/Y') }}</td>
                                <td class="py-1.5">{{ $payment->forma_pago }}</td>
                                <td class="py-1.5">{{ $payment->referencia ?: '—' }}</td>
                                <td class="py-1.5 text-right">${{ number_format($payment->monto, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($purchase->saldo_pendiente > 0)
                <form action="{{ route('purchases.payments.store', $purchase) }}" method="POST"
                      class="grid grid-cols-4 gap-3 items-end border-t border-gray-100 pt-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Fecha</label>
                        <input type="date" name="fecha" value="{{ now()->format('Y-m-d') }}"
                               class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Monto</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $purchase->saldo_pendiente }}" name="monto"
                               class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Forma de pago</label>
                        <select name="forma_pago" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Referencia</label>
                        <input type="text" name="referencia" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm">
                    </div>
                    @error('monto')
                        <p class="col-span-4 text-red-500 text-xs">{{ $message }}</p>
                    @enderror
                    <div class="col-span-4">
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                            Registrar abono
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    <a href="{{ route('purchases.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver a Compras</a>

</div>

@endsection
