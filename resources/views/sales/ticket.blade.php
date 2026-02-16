<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Venta #{{ $sale->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .ticket { max-width: 320px; margin: 0 auto; }
        .center { text-align: center; }
        .row { display: flex; justify-content: space-between; margin: 4px 0; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; font-size: 12px; border-collapse: collapse; }
        th, td { padding: 4px 0; text-align: left; }
        .right { text-align: right; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center">
            <h3>{{ $sale->company->name ?? 'Empresa' }}</h3>
            <p>Ticket de Venta #{{ $sale->id }}</p>
            <p>{{ $sale->created_at->format('d/m/Y H:i') }}</p>
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
                        <td class="right">${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="line"></div>

        <div class="row"><span>Subtotal:</span><span>${{ number_format($sale->subtotal ?? $sale->total, 2) }}</span></div>
        <div class="row"><span>IVA 13%:</span><span>${{ number_format($sale->tax_total ?? 0, 2) }}</span></div>
        <div class="row"><strong>Total:</strong><strong>${{ number_format($sale->total, 2) }}</strong></div>
        <div class="row"><span>Efectivo:</span><span>${{ number_format($sale->cash_received ?? $sale->total, 2) }}</span></div>
        <div class="row"><span>Cambio:</span><span>${{ number_format($sale->change_amount ?? 0, 2) }}</span></div>

        <div class="line"></div>
        <p class="center">Gracias por su compra</p>

        <div class="center no-print" style="margin-top: 12px;">
            <button onclick="window.print()">Imprimir</button>
        </div>
    </div>
</body>
</html>

