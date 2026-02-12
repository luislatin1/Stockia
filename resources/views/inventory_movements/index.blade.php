@extends('layouts.app')

@section('title', 'Movimientos de Inventario')

@section('content')

<div class="bg-white shadow rounded overflow-hidden">

    <table class="min-w-full text-sm">
        <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
                <th class="px-4 py-3 text-left">Fecha</th>
                <th class="px-4 py-3 text-left">Producto</th>
                <th class="px-4 py-3 text-left">Tipo</th>
                <th class="px-4 py-3 text-left">Cantidad</th>
                <th class="px-4 py-3 text-left">Referencia</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200">
            @foreach($movements as $movement)
            <tr>
                <td class="px-4 py-2">
                    {{ $movement->created_at->format('d/m/Y H:i') }}
                </td>

                <td class="px-4 py-2">
                    {{ $movement->product->name }}
                </td>

                <td class="px-4 py-2">
                    @if($movement->type === 'in')
                        <span class="text-green-600 font-semibold">Entrada</span>
                    @else
                        <span class="text-red-600 font-semibold">Salida</span>
                    @endif
                </td>

                <td class="px-4 py-2 font-medium">
                    {{ $movement->quantity }}
                </td>

                <td class="px-4 py-2 text-gray-500">
                    {{ $movement->reference }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>

<div class="mt-4">
    {{ $movements->links() }}
</div>

@endsection
