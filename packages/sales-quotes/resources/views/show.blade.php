@extends('layouts.app')

@section('title', 'Detalle Cotización')

@section('content')
@php
    $isAdminRole = in_array(function_exists('currentRole') ? currentRole() : null, ['Admin', 'SuperAdmin'], true);
@endphp
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $quote->quote_number ?: ('COT #' . $quote->id) }}</h2>
            <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($quote->created_at)->format('d/m/Y H:i') }}</p>
        </div>
        <a href="{{ route('salesquotes.index') }}" class="rounded border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700">
            Volver
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Cliente</p>
            <p class="mt-1 font-semibold">{{ $quote->customer_name }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Estado</p>
            <p class="mt-1 font-semibold">{{ ucfirst($quote->status) }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Vigencia</p>
            <p class="mt-1 font-semibold">{{ $quote->valid_until ? \Carbon\Carbon::parse($quote->valid_until)->format('d/m/Y') : 'Sin fecha' }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Producto</th>
                    <th class="px-4 py-3 text-right">Cantidad</th>
                    <th class="px-4 py-3 text-right">Precio</th>
                    <th class="px-4 py-3 text-right">Desc. %</th>
                    <th class="px-4 py-3 text-right">Desc. $</th>
                    <th class="px-4 py-3 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($items as $item)
                    <tr>
                        <td class="px-4 py-3">{{ $item->product_name }}</td>
                        <td class="px-4 py-3 text-right">{{ (int) $item->quantity }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format((float) $item->price, 2) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) ($item->discount_percent ?? 0), 2) }}%</td>
                        <td class="px-4 py-3 text-right">${{ number_format((float) ($item->discount_amount ?? 0), 2) }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format((float) $item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 text-sm">
                <tr>
                    <td colspan="5" class="px-4 py-2 text-right font-medium">Subtotal</td>
                    <td class="px-4 py-2 text-right">${{ number_format((float) $quote->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="px-4 py-2 text-right font-medium">IVA 13%</td>
                    <td class="px-4 py-2 text-right">${{ number_format((float) $quote->tax_total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="px-4 py-2 text-right font-bold">Total</td>
                    <td class="px-4 py-2 text-right font-bold">${{ number_format((float) $quote->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <form method="POST" action="{{ route('salesquotes.status.update', $quote->id) }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="block text-xs uppercase text-gray-500">Cambiar estado</label>
                <select name="status" class="mt-1 rounded border border-gray-300 px-3 py-2 text-sm">
                    <option value="draft" @selected($quote->status === 'draft')>Borrador</option>
                    <option value="sent" @selected($quote->status === 'sent')>Enviada</option>
                    <option value="approved" @selected($quote->status === 'approved') @disabled(! $isAdminRole)>Aprobada</option>
                    <option value="rejected" @selected($quote->status === 'rejected') @disabled(! $isAdminRole)>Rechazada</option>
                    <option value="expired" @selected($quote->status === 'expired') @disabled(! $isAdminRole)>Expirada</option>
                </select>
            </div>
            <button class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Actualizar</button>
        </form>
    </div>
</div>
@endsection
