<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Venta #{{ $sale->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 0; padding: 14px; font-size: 12px; }
        .ticket { width: 100%; max-width: 320px; margin: 0 auto; }
        .center { text-align: center; }
        .row { display: flex; justify-content: space-between; margin: 4px 0; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { padding: 3px 0; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center">
            <strong>{{ $sale->company->name ?? 'Empresa' }}</strong>
            <div>Ticket #{{ $sale->id }}</div>
            <div>{{ $sale->created_at->format('d/m/Y H:i') }}</div>
        </div>

        <div class="line"></div>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="right">Cant</th>
                    <th class="right">Subt.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">${{ number_format((float) $item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="line"></div>

        <div class="row"><span>Subtotal:</span><span>${{ number_format((float) ($sale->subtotal ?? 0), 2) }}</span></div>
        <div class="row"><span>IVA 13%:</span><span>${{ number_format((float) ($sale->tax_total ?? 0), 2) }}</span></div>
        <div class="row"><strong>Total:</strong><strong>${{ number_format((float) $sale->total, 2) }}</strong></div>
        <div class="row"><span>Efectivo:</span><span>${{ number_format((float) ($sale->cash_received ?? 0), 2) }}</span></div>
        <div class="row"><span>Cambio:</span><span>${{ number_format((float) ($sale->change_amount ?? 0), 2) }}</span></div>
    </div>
</body>
</html>
