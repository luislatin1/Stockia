<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante Venta #{{ $sale->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; margin: 0; padding: 18px; font-size: 12px; }
        h1 { margin: 0; font-size: 18px; }
        .meta { margin-top: 4px; font-size: 11px; color: #4b5563; white-space: pre-line; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 7px; font-size: 11px; }
        th { text-align: left; background: #f3f4f6; }
        .text-right { text-align: right; }
        .totals { margin-top: 12px; margin-left: auto; width: 280px; font-size: 11px; }
        .totals .row { display: flex; justify-content: space-between; padding: 3px 0; }
        .strong { font-weight: 700; }
        .block-note { margin-top: 12px; font-size: 11px; color: #374151; white-space: pre-line; }
        .footer { margin-top: 12px; font-size: 10px; color: #6b7280; white-space: pre-line; }
    </style>
</head>
<body>
    <h1>{{ $template->template_name ?: strtoupper($sale->document_type) }}</h1>
    <p class="meta">{{ $template->header_text ?: ($sale->company->legal_name ?: $sale->company->name) }}</p>
    <p class="meta">
        {{ $sale->company->name }}
        @if($sale->company->tax_id)
            | NIT: {{ $sale->company->tax_id }}
        @endif
        @if($sale->company->fiscal_phone)
            | Tel: {{ $sale->company->fiscal_phone }}
        @endif
        @if($sale->company->fiscal_email)
            | Email: {{ $sale->company->fiscal_email }}
        @endif
    </p>
    <p class="meta">
Documento: {{ strtoupper($sale->document_type) }} | Venta #{{ $sale->id }} | {{ $sale->created_at->format('d/m/Y H:i') }}
Caja/Almacen: {{ $sale->warehouse->name ?? ('#' . $sale->warehouse_id) }} | Cajero: {{ $sale->user->name ?? ('#' . $sale->user_id) }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-right">Cant.</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>{{ $item->product->name ?? ('#' . $item->product_id) }}</td>
                <td class="text-right">{{ (int) $item->quantity }}</td>
                <td class="text-right">${{ number_format((float) $item->price, 2) }}</td>
                <td class="text-right">${{ number_format((float) $item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>Subtotal</span><span>${{ number_format((float) $sale->subtotal, 2) }}</span></div>
        <div class="row"><span>IVA 13%</span><span>${{ number_format((float) $sale->tax_total, 2) }}</span></div>
        <div class="row strong"><span>Total</span><span>${{ number_format((float) $sale->total, 2) }}</span></div>
        <div class="row"><span>Efectivo</span><span>${{ number_format((float) $sale->cash_received, 2) }}</span></div>
        <div class="row"><span>Cambio</span><span>${{ number_format((float) $sale->change_amount, 2) }}</span></div>
    </div>

    @if(!empty($template->terms_text))
        <p class="block-note">{{ $template->terms_text }}</p>
    @endif
    @if(!empty($template->footer_text))
        <p class="footer">{{ $template->footer_text }}</p>
    @endif
</body>
</html>
