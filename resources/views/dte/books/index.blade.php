@extends('layouts.app')

@section('title', 'Libros Fiscales IVA')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Libros Fiscales IVA</h1>
        <p class="text-sm text-gray-500">Ventas a Contribuyente, Ventas a Consumidor y Compras del período seleccionado.</p>
    </div>

    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
        ⚠️ Reporte interno generado con los datos ya registrados en Stockia. El orden y formato exacto de columnas
        para presentar a Hacienda debe ser revisado por tu contador antes de usarlo como libro oficial.
    </div>

    <form method="GET" action="{{ route('dte.books.index') }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">Mes</label>
            <select name="month" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach (['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $label)
                    <option value="{{ $i + 1 }}" @selected($month === $i + 1)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-gray-700">Año</label>
            <select name="year" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach (range(now()->year, now()->year - 4) as $y)
                    <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Ver período</button>
    </form>

    {{-- Ventas a Contribuyente (CCF, tipo_dte 03) --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
            <h3 class="font-semibold text-gray-900">Libro de Ventas a Contribuyente (CCF)</h3>
            <a href="{{ route('dte.books.export', ['book' => 'contribuyente', 'year' => $year, 'month' => $month]) }}" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Exportar CSV
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600">Fecha</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600">Documento</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600">Cliente</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600">NRC</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">Gravadas</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">Exentas</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">IVA</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ventasContribuyente as $row)
                        <tr>
                            <td class="px-4 py-2">{{ \Illuminate\Support\Carbon::parse($row->created_at)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">{{ $row->numero_interno }}</td>
                            <td class="px-4 py-2">{{ $row->cliente_nombre ?: 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $row->cliente_nrc ?: '—' }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $row->gravadas, 2) }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $row->exentas, 2) }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $row->iva, 2) }}</td>
                            <td class="px-4 py-2 text-right font-medium">${{ number_format((float) $row->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">Sin ventas a contribuyente (CCF) en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right">Totales</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['contribuyente']['gravadas'], 2) }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['contribuyente']['exentas'], 2) }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['contribuyente']['iva'], 2) }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['contribuyente']['total'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Ventas a Consumidor (resumen diario) --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
            <h3 class="font-semibold text-gray-900">Libro de Ventas a Consumidor (resumen diario)</h3>
            <a href="{{ route('dte.books.export', ['book' => 'consumidor', 'year' => $year, 'month' => $month]) }}" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Exportar CSV
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600">Fecha</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600">Del documento</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600">Al documento</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">Cant.</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">Gravadas</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">Exentas</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">IVA</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ventasConsumidor as $row)
                        <tr>
                            <td class="px-4 py-2">{{ \Illuminate\Support\Carbon::parse($row->fecha)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">{{ $row->primer_documento }}</td>
                            <td class="px-4 py-2">{{ $row->ultimo_documento }}</td>
                            <td class="px-4 py-2 text-right">{{ $row->cantidad_documentos }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $row->gravadas, 2) }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $row->exentas, 2) }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $row->iva, 2) }}</td>
                            <td class="px-4 py-2 text-right font-medium">${{ number_format((float) $row->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">Sin ventas a consumidor en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right">Totales</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['consumidor']['gravadas'], 2) }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['consumidor']['exentas'], 2) }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['consumidor']['iva'], 2) }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['consumidor']['total'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Compras --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
            <h3 class="font-semibold text-gray-900">Libro de Compras</h3>
            <a href="{{ route('dte.books.export', ['book' => 'compras', 'year' => $year, 'month' => $month]) }}" class="rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Exportar CSV
            </a>
        </div>
        <p class="border-b border-gray-100 bg-gray-50 px-4 py-2 text-xs text-gray-500">
            Gravadas/Exentas se calculan a partir de si cada producto comprado está marcado como "Afecto IVA" en su ficha — no hay ningún dato que capturar a mano en el formulario de Compras.
        </p>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600">Fecha</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600">Documento</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600">Proveedor</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-600">NIT/NRC</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">Gravadas</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">Exentas</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">IVA</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($compras as $row)
                        <tr>
                            <td class="px-4 py-2">{{ \Illuminate\Support\Carbon::parse($row->fecha)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">{{ $row->numero_doc ?: '—' }}</td>
                            <td class="px-4 py-2">{{ $row->proveedor_nombre ?: 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $row->proveedor_nit ?: ($row->proveedor_nrc ?: '—') }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $row->gravadas, 2) }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $row->exentas, 2) }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format((float) $row->iva, 2) }}</td>
                            <td class="px-4 py-2 text-right font-medium">${{ number_format((float) $row->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">Sin compras registradas en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-right">Totales</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['compras']['gravadas'], 2) }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['compras']['exentas'], 2) }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['compras']['iva'], 2) }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($totales['compras']['total'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
