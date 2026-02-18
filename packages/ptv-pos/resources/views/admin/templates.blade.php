@extends('layouts.app')

@section('title', 'Plantillas POS')

@section('content')
<div class="max-w-5xl space-y-4">
    @if ($errors->any())
        <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('ptvpos.admin.templates.save') }}" class="space-y-4">
        @csrf
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm space-y-3">
            <h2 class="text-lg font-semibold text-gray-900">Plantilla Ticket</h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre plantilla</label>
                    <input name="ticket[template_name]" value="{{ old('ticket.template_name', $ticketTemplate->template_name) }}" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pie de página</label>
                    <input name="ticket[footer_text]" value="{{ old('ticket.footer_text', $ticketTemplate->footer_text) }}" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Encabezado</label>
                <textarea name="ticket[header_text]" rows="3" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">{{ old('ticket.header_text', $ticketTemplate->header_text) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Condiciones / Mensajes</label>
                <textarea name="ticket[terms_text]" rows="3" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">{{ old('ticket.terms_text', $ticketTemplate->terms_text) }}</textarea>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm space-y-3">
            <h2 class="text-lg font-semibold text-gray-900">Plantilla Factura</h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre plantilla</label>
                    <input name="factura[template_name]" value="{{ old('factura.template_name', $invoiceTemplate->template_name) }}" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pie de página</label>
                    <input name="factura[footer_text]" value="{{ old('factura.footer_text', $invoiceTemplate->footer_text) }}" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Encabezado</label>
                <textarea name="factura[header_text]" rows="3" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">{{ old('factura.header_text', $invoiceTemplate->header_text) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Condiciones / Mensajes</label>
                <textarea name="factura[terms_text]" rows="3" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">{{ old('factura.terms_text', $invoiceTemplate->terms_text) }}</textarea>
            </div>
        </div>

        <button class="rounded bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Guardar plantillas</button>
    </form>
</div>
@endsection
