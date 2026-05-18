<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $dteData['tipo_dte_label'] }} #{{ $sale->id }}</title>
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
        h1 { margin: 0; font-size: 20px; }
        h2 { margin: 12px 0 6px 0; font-size: 14px; }
        p { margin: 2px 0; font-size: 12px; }
        .meta p { color: var(--muted); }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
        }
        th, td { border: 1px solid var(--line); padding: 8px; vertical-align: top; }
        th { text-align: left; color: #374151; }
        .text-right { text-align: right; }
        .section { border: 1px solid var(--line); padding: 8px; margin-top: 10px; }
        .cols { display: flex; gap: 10px; }
        .col { flex: 1; border: 1px solid var(--line); padding: 8px; }
        .qr-wrap { margin-top: 12px; text-align: center; }
        .qr-wrap img { width: 128px; height: 128px; border: 1px solid var(--line); }
        .footer {
            margin-top: 16px;
            font-size: 11px;
            color: var(--muted);
            text-align: center;
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
        <button type="button" class="btn btn-print" onclick="window.print()">Imprimir ahora</button>
        <a href="{{ route('ptvpos.sales.pdf', $sale->id) }}" target="_blank" class="btn btn-print" style="text-decoration: none;">Abrir PDF</a>
        <form method="POST" action="{{ route('ptvpos.sales.print.complete', $sale->id) }}">
            @csrf
            <input type="hidden" name="printed_ack" value="1">
            <button class="btn btn-finish">Marcar impreso y finalizar venta</button>
        </form>
    </div>

    <div class="sheet-wrap">
        <article class="sheet">
            <h1>{{ $dteData['tipo_dte_label'] }}</h1>
            <div class="section meta">
                <p><strong>Número de Control:</strong> {{ $dteData['numero_control'] }}</p>
                <p><strong>Código de Generación:</strong> {{ $dteData['codigo_generacion'] }}</p>
                <p><strong>Sello de Recepción MH:</strong> {{ $dteData['sello_recepcion'] }}</p>
                <p><strong>Fecha y Hora de Emisión:</strong> {{ $dteData['fecha_emision'] }} {{ $dteData['hora_emision'] }}</p>
                <p><strong>Ambiente:</strong> {{ $dteData['ambiente_label'] }} ({{ $dteData['ambiente_code'] }})</p>
                <p><strong>Condición de Operación:</strong> {{ $dteData['condicion_operacion_label'] }} ({{ $dteData['condicion_operacion'] }})</p>
            </div>

            <div class="cols">
                <div class="col">
                    <h2>Datos del Emisor</h2>
                    <p><strong>Nombre:</strong> {{ $dteData['emisor']['nombre'] }}</p>
                    <p><strong>NIT:</strong> {{ $dteData['emisor']['nit'] }}</p>
                    <p><strong>NRC:</strong> {{ $dteData['emisor']['nrc'] }}</p>
                    <p><strong>Actividad:</strong> {{ $dteData['emisor']['codActividad'] }} {{ $dteData['emisor']['descActividad'] }}</p>
                    <p><strong>Dirección:</strong> {{ $dteData['emisor']['direccion'] }}</p>
                    <p><strong>Tel:</strong> {{ $dteData['emisor']['telefono'] }}</p>
                    <p><strong>Correo:</strong> {{ $dteData['emisor']['correo'] }}</p>
                </div>
                <div class="col">
                    <h2>Datos del Receptor</h2>
                    <p><strong>Nombre:</strong> {{ $dteData['receptor']['nombre'] }}</p>
                    <p><strong>Tipo Doc:</strong> {{ $dteData['receptor']['tipoDocumento'] }}</p>
                    <p><strong>Número Doc:</strong> {{ $dteData['receptor']['numDocumento'] }}</p>
                    <p><strong>NRC:</strong> {{ $dteData['receptor']['nrc'] }}</p>
                    <p><strong>Dirección:</strong> {{ $dteData['receptor']['direccion'] }}</p>
                    <p><strong>Tel:</strong> {{ $dteData['receptor']['telefono'] }}</p>
                    <p><strong>Correo:</strong> {{ $dteData['receptor']['correo'] }}</p>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th class="text-right">Cant.</th>
                        <th>Unidad</th>
                        <th>Descripción</th>
                        <th class="text-right">Precio Unit.</th>
                        <th class="text-right">Desc.</th>
                        <th class="text-right">Venta Gravada</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dteData['detalle'] as $item)
                    <tr>
                        <td>{{ $item['numItem'] ?? '-' }}</td>
                        <td class="text-right">{{ number_format((float) ($item['cantidad'] ?? 0), 2, '.', ',') }}</td>
                        <td>{{ $item['uniMedida'] ?? '-' }}</td>
                        <td>{{ $item['descripcion'] ?? '-' }}</td>
                        <td class="text-right">{{ number_format((float) ($item['precioUni'] ?? 0), 2, '.', ',') }}</td>
                        <td class="text-right">{{ number_format((float) ($item['montoDescu'] ?? 0), 2, '.', ',') }}</td>
                        <td class="text-right">{{ number_format((float) ($item['ventaGravada'] ?? 0), 2, '.', ',') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7">Sin detalle disponible.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if(in_array($dteData['tipo_dte'], ['05', '06'], true))
                <div class="section">
                    <h2>Documento Relacionado</h2>
                    @forelse($dteData['referencias'] as $ref)
                        <p><strong>Tipo:</strong> {{ $ref['tipoDocumento'] ?? ($ref['tipoDte'] ?? 'N/D') }}</p>
                        <p><strong>Número de Control:</strong> {{ $ref['numeroControl'] ?? 'N/D' }}</p>
                        <p><strong>Código de Generación:</strong> {{ $ref['codigoGeneracion'] ?? 'N/D' }}</p>
                        <p><strong>Fecha:</strong> {{ $ref['fechaEmision'] ?? ($ref['fecEmi'] ?? 'N/D') }}</p>
                    @empty
                        <p><strong>Tipo:</strong> N/D</p>
                        <p><strong>Número de Control:</strong> N/D</p>
                        <p><strong>Código de Generación:</strong> N/D</p>
                        <p><strong>Fecha:</strong> N/D</p>
                    @endforelse
                    <p><strong>Motivo:</strong> {{ $dteData['motivo'] !== '' ? $dteData['motivo'] : 'N/D' }}</p>
                </div>
            @endif

            <table>
                <tbody>
                    <tr><td>Sub Total</td><td class="text-right">{{ number_format((float) ($dteData['resumen']['subTotal'] ?? 0), 2, '.', ',') }}</td></tr>
                    <tr><td>Total Descuento</td><td class="text-right">{{ number_format((float) ($dteData['resumen']['totalDescu'] ?? 0), 2, '.', ',') }}</td></tr>
                    <tr><td>Venta No Sujeta</td><td class="text-right">{{ number_format((float) ($dteData['resumen']['totalNoSuj'] ?? 0), 2, '.', ',') }}</td></tr>
                    <tr><td>Venta Exenta</td><td class="text-right">{{ number_format((float) ($dteData['resumen']['totalExenta'] ?? 0), 2, '.', ',') }}</td></tr>
                    <tr><td>Venta Gravada</td><td class="text-right">{{ number_format((float) ($dteData['resumen']['totalGravada'] ?? 0), 2, '.', ',') }}</td></tr>
                    <tr><td>IVA 13%</td><td class="text-right">{{ number_format((float) ($dteData['resumen']['iva'] ?? 0), 2, '.', ',') }}</td></tr>
                    <tr><td>IVA Retenido 1%</td><td class="text-right">{{ number_format((float) ($dteData['resumen']['ivaRete1'] ?? 0), 2, '.', ',') }}</td></tr>
                    <tr><td>Renta retenida</td><td class="text-right">{{ number_format((float) ($dteData['resumen']['reteRenta'] ?? 0), 2, '.', ',') }}</td></tr>
                    <tr><td><strong>Total a Pagar</strong></td><td class="text-right"><strong>{{ number_format((float) ($dteData['resumen']['totalPagar'] ?? 0), 2, '.', ',') }}</strong></td></tr>
                </tbody>
            </table>

            @if($dteData['tipo_dte'] === '07')
                <div class="section">
                    <h2>Comprobante de Retención</h2>
                    <p><strong>Base sujeta:</strong> {{ number_format((float) ($dteData['resumen']['totalGravada'] ?? 0), 2, '.', ',') }}</p>
                    <p><strong>Porcentaje retenido:</strong> 1%</p>
                    <p><strong>Monto retenido:</strong> {{ number_format((float) ($dteData['resumen']['ivaRete1'] ?? 0), 2, '.', ',') }}</p>
                    <p><strong>Periodo fiscal:</strong> {{ $dteData['periodo_fiscal'] !== '' ? $dteData['periodo_fiscal'] : 'N/D' }}</p>
                </div>
            @endif

            @if($dteData['tipo_dte'] === '08')
                <div class="section">
                    <h2>Comprobante de Liquidación</h2>
                    <p><strong>Datos del proveedor informal:</strong> {{ $dteData['receptor']['nombre'] }}</p>
                    <p><strong>Retenciones aplicadas:</strong> IVA Retenido {{ number_format((float) ($dteData['resumen']['ivaRete1'] ?? 0), 2, '.', ',') }} / Renta {{ number_format((float) ($dteData['resumen']['reteRenta'] ?? 0), 2, '.', ',') }}</p>
                    <p><strong>Firma digital visible:</strong> {{ $dteData['firma_digital'] !== '' ? $dteData['firma_digital'] : 'N/D' }}</p>
                </div>
            @endif

            <div class="qr-wrap">
                <img src="{{ $dteData['qr_image_url'] }}" alt="QR de consulta pública MH">
                <p>Consulta este documento en el siguiente QR</p>
                <p>{{ $dteData['qr_public_url'] }}</p>
            </div>

            <p class="footer">Documento Tributario Electrónico válido según normativa DGII</p>
        </article>
    </div>
</body>
<script>
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.print();
        }, 300);
    });
</script>
</html>
