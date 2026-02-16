<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Local Venta #{{ $sale->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; color: #111; }
        .container { max-width: 780px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .title { font-size: 22px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; font-size: 14px; }
        th { background: #f4f4f4; text-align: left; }
        .right { text-align: right; }
        .totals { margin-top: 16px; margin-left: auto; width: 320px; }
        .totals .row { display: flex; justify-content: space-between; padding: 4px 0; }
        .bold { font-weight: bold; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <div class="title">Factura Local</div>
                <div>{{ $sale->company->name ?? 'Empresa' }}</div>
                <div>Venta #{{ $sale->id }}</div>
            </div>
            <div class="right">
                <div>Fecha: {{ $sale->created_at->format('d/m/Y H:i') }}</div>
                <div>Almacén: {{ $sale->warehouse->name ?? 'N/A' }}</div>
                <div>Cajero: {{ $sale->user->name ?? 'N/A' }}</div>
            </div>
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
                        <td class="right">${{ number_format($item->price, 2) }}</td>
                        <td class="right">${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row"><span>Subtotal:</span><span>${{ number_format($sale->subtotal ?? $sale->total, 2) }}</span></div>
            <div class="row"><span>IVA 13%:</span><span>${{ number_format($sale->tax_total ?? 0, 2) }}</span></div>
            <div class="row bold"><span>Total:</span><span>${{ number_format($sale->total, 2) }}</span></div>
            <div class="row"><span>Efectivo Recibido:</span><span>${{ number_format($sale->cash_received ?? $sale->total, 2) }}</span></div>
            <div class="row"><span>Cambio:</span><span>${{ number_format($sale->change_amount ?? 0, 2) }}</span></div>
        </div>

        <div class="no-print" style="margin-top: 20px;">
            <button onclick="window.print()">Imprimir Factura</button>
        </div>
    </div>
</body>
</html>

