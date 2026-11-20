<?php

namespace Stockia\DteSvMh\Builders;

use Carbon\CarbonInterface;

/**
 * Evento de Retorno (CAT-002 tipo 18, esquema fe-eret-v1) aplicado a una Factura (01).
 *
 * El retorno reporta bienes devueltos o reembolsos sin ajuste de crédito fiscal (Manual Funcional
 * v2.0 §X). Los eventos NO llevan número de control: se identifican solo por código de generación.
 * Los valores van como en la Factura: precios con IVA incluido en lo gravado; el cuerpo a 8
 * decimales y el resumen a 2.
 *
 * Entrada ($ctx):
 *  ambiente, codigo_generacion, fecha (CarbonInterface hora de El Salvador), contingencia ?{tipo,motivo},
 *  emisor{nit, nombre, codEstableMH, codEstable, codPuntoVentaMH, codPuntoVenta},
 *  documento ?{tipoDocumento, numDocumento, nombre, telefono, correo} (receptor de la Factura; null si no lo tenía),
 *  relacionados[{tipo_documento '01', codigo_generacion, fecha}],
 *  items[{documento (codigo de generacion de la factura), codigo, descripcion, cantidad, precio (SIN IVA),
 *         categoria gravada|exenta|no_sujeta, tipo_item, uni_medida}], tasa_iva.
 */
class EventoRetornoBuilder
{
    public function build(array $ctx): array
    {
        $docs = $ctx['relacionados'] ?? [];
        if ($docs === [] || count($docs) > 50) {
            throw new \RuntimeException('El evento de retorno requiere entre 1 y 50 documentos relacionados.');
        }
        foreach ($docs as $d) {
            if (($d['tipo_documento'] ?? null) !== '01') {
                throw new \RuntimeException('Por ahora el Evento de Retorno solo se aplica a Facturas (01).');
            }
        }
        if (count($ctx['items'] ?? []) === 0 || count($ctx['items']) > 2000) {
            throw new \RuntimeException('El evento de retorno requiere entre 1 y 2000 ítems.');
        }

        $tasa = (float) ($ctx['tasa_iva'] ?? 0.13);
        $cuerpo = [];
        $tot = ['no_suj' => 0.0, 'exenta' => 0.0, 'gravada' => 0.0, 'iva' => 0.0];

        foreach (array_values($ctx['items']) as $i => $it) {
            $cat = (string) ($it['categoria'] ?? 'gravada');
            $cantidad = round((float) $it['cantidad'], 8);
            $precio = round((float) $it['precio'] * ($cat === 'gravada' ? 1 + $tasa : 1.0), 8);
            $venta = max(0.0, round($cantidad * $precio, 8));
            $gravada = $cat === 'gravada' ? $venta : 0.0;
            $iva = $gravada > 0 ? round($gravada / (1 + $tasa) * $tasa, 8) : 0.0;

            $tot['no_suj'] += $cat === 'no_sujeta' ? $venta : 0.0;
            $tot['exenta'] += $cat === 'exenta' ? $venta : 0.0;
            $tot['gravada'] += $gravada;
            $tot['iva'] += $iva;

            $codigo = trim((string) ($it['codigo'] ?? ''));
            $cuerpo[] = [
                'numItem' => $i + 1,
                'tipoItem' => (int) ($it['tipo_item'] ?? 1),
                'codigoGeneracion' => strtoupper((string) $it['documento']),
                'cantidad' => $cantidad,
                'precioUni' => $precio,
                'descripcion' => mb_substr(trim((string) ($it['descripcion'] ?? '')) ?: 'ITEM', 0, 1500),
                'codigo' => $codigo === '' ? null : mb_substr($codigo, 0, 25),
                'uniMedida' => (int) ($it['uni_medida'] ?? 59),
                'montoDescu' => 0.0,
                'codTributo' => null,
                'ventaNoSuj' => $cat === 'no_sujeta' ? $venta : 0.0,
                'ventaExenta' => $cat === 'exenta' ? $venta : 0.0,
                'ventaGravada' => $gravada,
                'compra' => 0.0,
                'tributos' => null,
                'psv' => 0.0,
                'ivaItem' => $iva,
                'noGravado' => 0.0,
                'seguro' => 0.0,
                'flete' => 0.0,
                'ivaRete' => 0.0,
                'reteRenta' => 0.0,
            ];
        }

        $noSuj = round($tot['no_suj'], 2);
        $exenta = round($tot['exenta'], 2);
        $gravada = round($tot['gravada'], 2);
        $sub = round($noSuj + $exenta + $gravada, 2);

        /** @var CarbonInterface $fecha */
        $fecha = $ctx['fecha'];
        $conting = $ctx['contingencia'] ?? null;

        return [
            'identificacion' => [
                'version' => 1,
                'ambiente' => (string) $ctx['ambiente'],
                'tipoModelo' => $conting ? 2 : 1,
                'tipoOperacion' => $conting ? 2 : 1,
                'tipoEvento' => '18',
                'tipoContingencia' => $conting ? (int) $conting['tipo'] : null,
                // El esquema del evento solo admite motivo cuando el tipo de contingencia es 5 (otro).
                'motivoContin' => $conting && (int) $conting['tipo'] === 5 ? (string) $conting['motivo'] : null,
                'codigoGeneracion' => strtoupper((string) $ctx['codigo_generacion']),
                'fecEmi' => $fecha->format('Y-m-d'),
                'horEmi' => $fecha->format('H:i:s'),
                'fusion' => null,
                'tipoMoneda' => 'USD',
            ],
            'documentoRelacionado' => array_map(fn ($d) => [
                'tipoDocumento' => '01',
                'codigoGeneracion' => strtoupper((string) $d['codigo_generacion']),
                'fechaEmision' => (string) $d['fecha'],
            ], array_values($docs)),
            'emisor' => $ctx['emisor'] + [
                'recintoFiscal' => null, 'tipoRegimen' => null, 'regimen' => null, 'tipoItemExpor' => null,
            ],
            'documento' => $ctx['documento'] ?? null,
            'ventaTercero' => null,
            'compraTercero' => null,
            'cuerpoDocumento' => $cuerpo,
            'resumen' => [
                'totalNoSuj' => $noSuj,
                'totalExenta' => $exenta,
                'totalGravada' => $gravada,
                'totalCompraExcluidos' => 0.0,
                'subTotalVentas' => $sub,
                'tributos' => null,
                'totalSeguro' => null,
                'totalFlete' => null,
                'montoTotalOperacion' => $sub,
                'ivaRete' => 0.0,
                'reteRenta' => null,
                'totalNoGravado' => 0.0,
                'totalPagar' => $sub,
                'totalLetras' => (new DteV2Builder())->aLetras($sub),
                'totalNoOnerosas' => 0.0,
                'totalIva' => round($tot['iva'], 2),
                'saldoFavor' => 0.0,
            ],
            'apendice' => null,
        ];
    }
}
