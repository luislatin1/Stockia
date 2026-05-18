<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $dteData['tipo_dte_label'] }} #{{ $sale->id }}</title>
    <style>
        @page { size: letter; margin: 18mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111; font-size: 11px; }
        h1 { margin: 0 0 8px 0; font-size: 18px; }
        h2 { margin: 12px 0 6px 0; font-size: 13px; }
        p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; font-size: 10px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .box { border: 1px solid #d1d5db; padding: 8px; margin-top: 8px; }
        .meta p { margin: 3px 0; }
        .grid { width: 100%; }
        .grid td { border: none; padding: 0 8px 0 0; vertical-align: top; width: 50%; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .qr-wrap { margin-top: 12px; text-align: center; }
        .qr-wrap img { width: 120px; height: 120px; border: 1px solid #d1d5db; }
        .small { font-size: 9px; word-break: break-all; }
        .footer { margin-top: 14px; font-size: 10px; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $dteData['tipo_dte_label'] }}</h1>

    <div class="box meta">
        <p><strong>Número de Control:</strong> {{ $dteData['numero_control'] }}</p>
        <p><strong>Código de Generación:</strong> {{ $dteData['codigo_generacion'] }}</p>
        <p><strong>Sello de Recepción MH:</strong> {{ $dteData['sello_recepcion'] }}</p>
        <p><strong>Fecha y Hora de Emisión:</strong> {{ $dteData['fecha_emision'] }} {{ $dteData['hora_emision'] }}</p>
        <p><strong>Ambiente:</strong> {{ $dteData['ambiente_label'] }} ({{ $dteData['ambiente_code'] }})</p>
    </div>

    <table class="grid">
        <tr>
            <td>
                <h2>Datos del Emisor</h2>
                <p><strong>Nombre o Razón Social:</strong> {{ $dteData['emisor']['nombre'] }}</p>
                <p><strong>NIT:</strong> {{ $dteData['emisor']['nit'] }}</p>
                <p><strong>NRC:</strong> {{ $dteData['emisor']['nrc'] }}</p>
                <p><strong>Actividad Económica:</strong> {{ $dteData['emisor']['codActividad'] }} {{ $dteData['emisor']['descActividad'] }}</p>
                <p><strong>Dirección:</strong> {{ $dteData['emisor']['direccion'] }}</p>
                <p><strong>Teléfono:</strong> {{ $dteData['emisor']['telefono'] }}</p>
                <p><strong>Correo:</strong> {{ $dteData['emisor']['correo'] }}</p>
            </td>
            <td>
                <h2>Datos del Receptor</h2>
                <p><strong>Nombre:</strong> {{ $dteData['receptor']['nombre'] }}</p>
                <p><strong>Tipo Documento:</strong> {{ $dteData['receptor']['tipoDocumento'] }}</p>
                <p><strong>Número Documento:</strong> {{ $dteData['receptor']['numDocumento'] }}</p>
                <p><strong>NRC:</strong> {{ $dteData['receptor']['nrc'] }}</p>
                <p><strong>Dirección:</strong> {{ $dteData['receptor']['direccion'] }}</p>
                <p><strong>Teléfono:</strong> {{ $dteData['receptor']['telefono'] }}</p>
                <p><strong>Correo:</strong> {{ $dteData['receptor']['correo'] }}</p>
            </td>
        </tr>
    </table>

    @if(in_array($dteData['tipo_dte'], ['05', '06'], true))
        <div class="box">
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

    <h2>Detalle de Ítems</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cant</th>
                <th>Unidad</th>
                <th>Descripción</th>
                <th class="right">Precio Unit</th>
                <th class="right">Desc</th>
                <th class="right">Venta Gravada</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dteData['detalle'] as $item)
                <tr>
                    <td>{{ $item['numItem'] ?? '-' }}</td>
                    <td>{{ number_format((float) ($item['cantidad'] ?? 0), 2, '.', ',') }}</td>
                    <td>{{ $item['uniMedida'] ?? '-' }}</td>
                    <td>{{ $item['descripcion'] ?? '-' }}</td>
                    <td class="right">{{ number_format((float) ($item['precioUni'] ?? 0), 2, '.', ',') }}</td>
                    <td class="right">{{ number_format((float) ($item['montoDescu'] ?? 0), 2, '.', ',') }}</td>
                    <td class="right">{{ number_format((float) ($item['ventaGravada'] ?? 0), 2, '.', ',') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Sin detalle disponible.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Resumen de Totales</h2>
    <table>
        <tbody>
            <tr><td>Sub Total</td><td class="right">{{ number_format((float) ($dteData['resumen']['subTotal'] ?? 0), 2, '.', ',') }}</td></tr>
            <tr><td>Total Descuento</td><td class="right">{{ number_format((float) ($dteData['resumen']['totalDescu'] ?? 0), 2, '.', ',') }}</td></tr>
            <tr><td>Venta No Sujeta</td><td class="right">{{ number_format((float) ($dteData['resumen']['totalNoSuj'] ?? 0), 2, '.', ',') }}</td></tr>
            <tr><td>Venta Exenta</td><td class="right">{{ number_format((float) ($dteData['resumen']['totalExenta'] ?? 0), 2, '.', ',') }}</td></tr>
            <tr><td>Venta Gravada</td><td class="right">{{ number_format((float) ($dteData['resumen']['totalGravada'] ?? 0), 2, '.', ',') }}</td></tr>
            <tr><td>IVA 13%</td><td class="right">{{ number_format((float) ($dteData['resumen']['iva'] ?? 0), 2, '.', ',') }}</td></tr>
            <tr><td>IVA Retenido 1%</td><td class="right">{{ number_format((float) ($dteData['resumen']['ivaRete1'] ?? 0), 2, '.', ',') }}</td></tr>
            <tr><td>Renta retenida</td><td class="right">{{ number_format((float) ($dteData['resumen']['reteRenta'] ?? 0), 2, '.', ',') }}</td></tr>
            <tr><td>Monto Total Operación</td><td class="right">{{ number_format((float) ($dteData['resumen']['montoTotalOperacion'] ?? 0), 2, '.', ',') }}</td></tr>
            <tr class="bold"><td>Total a Pagar</td><td class="right">{{ number_format((float) ($dteData['resumen']['totalPagar'] ?? 0), 2, '.', ',') }}</td></tr>
            <tr><td>Condición de Operación</td><td class="right">{{ $dteData['condicion_operacion_label'] }} ({{ $dteData['condicion_operacion'] }})</td></tr>
        </tbody>
    </table>

    @if($dteData['tipo_dte'] === '07')
        <div class="box">
            <h2>Comprobante de Retención</h2>
            <p><strong>Base sujeta:</strong> {{ number_format((float) ($dteData['resumen']['totalGravada'] ?? 0), 2, '.', ',') }}</p>
            <p><strong>Porcentaje retenido:</strong> 1%</p>
            <p><strong>Monto retenido:</strong> {{ number_format((float) ($dteData['resumen']['ivaRete1'] ?? 0), 2, '.', ',') }}</p>
            <p><strong>Periodo fiscal:</strong> {{ $dteData['periodo_fiscal'] !== '' ? $dteData['periodo_fiscal'] : 'N/D' }}</p>
        </div>
    @endif

    @if($dteData['tipo_dte'] === '08')
        <div class="box">
            <h2>Comprobante de Liquidación</h2>
            <p><strong>Datos del proveedor informal:</strong> {{ $dteData['receptor']['nombre'] }}</p>
            <p><strong>Retenciones aplicadas:</strong> IVA Retenido {{ number_format((float) ($dteData['resumen']['ivaRete1'] ?? 0), 2, '.', ',') }} / Renta {{ number_format((float) ($dteData['resumen']['reteRenta'] ?? 0), 2, '.', ',') }}</p>
            <p><strong>Firma digital visible:</strong> {{ $dteData['firma_digital'] !== '' ? $dteData['firma_digital'] : 'N/D' }}</p>
        </div>
    @endif

    <div class="qr-wrap">
        <img src="{{ $dteData['qr_image_url'] }}" alt="QR de consulta pública MH">
        <p>Consulta este documento en el siguiente QR</p>
        <p class="small">{{ $dteData['qr_public_url'] }}</p>
    </div>

    <div class="footer">
        Documento Tributario Electrónico válido según normativa DGII
    </div>
</body>
</html>
