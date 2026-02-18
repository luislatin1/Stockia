<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Venta #{{ $sale->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 0; padding: 18px; color: #111; font-size: 12px; }
        .container { width: 100%; }
        .header { margin-bottom: 14px; }
        .title { font-size: 18px; font-weight: 700; }
        .meta { font-size: 11px; color: #444; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 7px; font-size: 11px; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
        .totals { margin-top: 12px; margin-left: auto; width: 280px; }
        .totals .row { display: flex; justify-content: space-between; padding: 3px 0; }
        .strong { font-weight: 700; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">Factura Local</div>
            <div>{{ $sale->company->name ?? 'Empresa' }}</div>
            <div class="meta">Venta #{{ $sale->id }} | {{ $sale->created_at->format('d/m/Y H:i') }}</div>
            <div class="meta">Almacen: {{ $sale->warehouse->name ?? 'N/A' }} | Cajero: {{ $sale->user->name ?? 'N/A' }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="right">Cantidad</th>
                    <th class="right">Precio Base</th>
                    <th class="right">Subtotal Base</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">${{ number_format((float) $item->price, 2) }}</td>
                        <td class="right">${{ number_format((float) $item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row"><span>Subtotal:</span><span>${{ number_format((float) ($sale->subtotal ?? 0), 2) }}</span></div>
            <div class="row"><span>IVA 13%:</span><span>${{ number_format((float) ($sale->tax_total ?? 0), 2) }}</span></div>
            <div class="row strong"><span>Total:</span><span>${{ number_format((float) $sale->total, 2) }}</span></div>
            <div class="row"><span>Efectivo Recibido:</span><span>${{ number_format((float) ($sale->cash_received ?? 0), 2) }}</span></div>
            <div class="row"><span>Cambio:</span><span>${{ number_format((float) ($sale->change_amount ?? 0), 2) }}</span></div>
        </div>
    </div>
</body>
</html>
