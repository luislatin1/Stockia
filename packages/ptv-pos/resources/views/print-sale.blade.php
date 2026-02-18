<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante Venta #{{ $sale->id }}</title>
    <style>
        :root {
            --fg: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --paper: #ffffff;
            --bg: #f3f4f6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: var(--fg);
            background: var(--bg);
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid var(--line);
            background: #fff;
        }
        .toolbar p { margin: 0; font-size: 13px; color: #92400e; }
        .btn {
            border: 0;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-print { background: #4338ca; color: #fff; }
        .btn-finish { background: #065f46; color: #fff; }
        .sheet-wrap {
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        .sheet {
            width: 100%;
            max-width: 820px;
            background: var(--paper);
            border: 1px solid var(--line);
            padding: 24px;
        }
        h1 {
            margin: 0;
            font-size: 20px;
        }
        .meta {
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.4;
            white-space: pre-line;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 13px;
        }
        th, td {
            border-bottom: 1px solid var(--line);
            padding: 8px;
        }
        th { text-align: left; color: #374151; }
        .text-right { text-align: right; }
        .totals {
            margin-top: 12px;
            margin-left: auto;
            width: 300px;
            font-size: 13px;
        }
        .totals .row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }
        .totals .strong { font-weight: 700; }
        .block-note {
            margin-top: 12px;
            font-size: 12px;
            color: #374151;
            white-space: pre-line;
        }
        .footer {
            margin-top: 16px;
            font-size: 11px;
            color: var(--muted);
            white-space: pre-line;
        }
        @media print {
            @page {
                size: auto;
                margin: 10mm;
            }
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet-wrap { padding: 0; }
            .sheet {
                border: 0;
                max-width: none;
                width: auto;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <p>Venta #{{ $sale->id }} pendiente de impresion.</p>
        <button type="button" class="btn btn-print" onclick="window.print()">Imprimir / Guardar PDF</button>
        <form method="POST" action="{{ route('ptvpos.sales.print.complete', $sale->id) }}">
            @csrf
            <input type="hidden" name="printed_ack" value="1">
            <button class="btn btn-finish">Marcar impreso y finalizar venta</button>
        </form>
    </div>

    <div class="sheet-wrap">
        <article class="sheet">
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
        </article>
    </div>
</body>
</html>
