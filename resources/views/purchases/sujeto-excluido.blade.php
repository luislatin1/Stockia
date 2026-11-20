@extends('layouts.app')

@section('title', 'Factura de Sujeto Excluido')

@section('content')
<div class="max-w-3xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Factura de Sujeto Excluido</h2>
        <p class="text-sm text-gray-500">Compra #{{ $purchase->id }} | Proveedor: {{ $provider->nombre }}</p>
    </div>

    @if ($errors->any())
        <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900 space-y-1">
        <p>Se emite cuando <strong>usted compra</strong> a una persona que no es contribuyente de IVA. El proveedor es el "sujeto excluido" y debe tener DUI o NIT válido (actual:
            <strong>{{ $provider->dui ?: ($provider->nit ?: 'sin documento') }}</strong>; se edita en Proveedores).</p>
        <p>No lleva IVA. Si aplica retención de renta, indique el monto: se descuenta del total a pagar.</p>
        <p class="text-xs">La dirección del vendedor queda guardada en el proveedor para próximas compras.</p>
    </div>

    <form method="POST" action="{{ route('purchases.sujeto-excluido.store', $purchase) }}" class="space-y-4">
        @csrf

        <div class="rounded border border-gray-200 bg-white p-4 space-y-3">
            <h3 class="font-semibold text-gray-900">Dirección del vendedor</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                @include('dte::partials.ubicacion-select', [
                    'prefix' => 'fse',
                    'dep' => old('departamento', $provider->departamento),
                    'mun' => old('municipio', $provider->municipio),
                    'dis' => old('distrito', $provider->distrito),
                    'departamentos' => $departamentos,
                    'municipios' => $municipios,
                    'distritos' => $distritos,
                ])
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Dirección</label>
                <input name="direccion" maxlength="200" value="{{ old('direccion', $provider->direccion) }}" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
            </div>
        </div>

        <div class="rounded border border-gray-200 bg-white p-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">Retención de renta ($)</label>
                <input type="number" step="0.01" min="0" name="retencion_renta" value="{{ old('retencion_renta', '0.00') }}" class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-right">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                <input name="observaciones" maxlength="500" value="{{ old('observaciones') }}" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
            </div>
        </div>

        <div class="rounded border border-gray-200 bg-white p-4 text-sm">
            <p class="font-semibold text-gray-900 mb-2">Detalle de la compra</p>
            <ul class="divide-y">
                @foreach($purchase->items as $item)
                    <li class="flex justify-between py-1"><span>{{ $item->product->name ?? ('#'.$item->product_id) }} × {{ (int) $item->cantidad }}</span><span>${{ number_format((float) $item->subtotal, 2) }}</span></li>
                @endforeach
            </ul>
            <p class="mt-2 text-right font-semibold">Total compra: ${{ number_format((float) $purchase->items->sum('subtotal'), 2) }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.show', $purchase) }}" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Volver</a>
            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Emitir factura de sujeto excluido</button>
        </div>
    </form>
</div>
@endsection
