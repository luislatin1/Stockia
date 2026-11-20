<?php

namespace Stockia\DteSvMh\Builders;

use Carbon\CarbonInterface;

/**
 * Factura de Exportación (11, esquema fe-fex-v3).
 *
 * Reglas (Normativa v2.0 Anexo IV, leídas del OCR; ver FASE2_AVANCE):
 *  - Las ventas por exportación van como `ventaGravada` con el tributo C3 (IVA 0 %) y valor 0.00: no hay IVA.
 *  - Precios sin IVA (no hay IVA que incluir). Cuerpo a 8 decimales, resumen a 2.
 *  - `montoTotalOperacion` = totalGravada − descuento global + seguro + flete (+ tributos, que valen 0).
 *  - Tipo de exportación (emisor.tipoItemExpor, CAT-011): 1 bienes, 2 servicios. Con 1 se exigen recinto fiscal
 *    (CAT-027), tipo de régimen (CAT-033) y régimen (CAT-028); con 2 van null.
 *
 * Entrada ($ctx): ambiente, numero_control, codigo_generacion, fecha, contingencia ?{tipo,motivo},
 *  emisor{nit,nrc,nombre,codActividad,descActividad,nombreComercial,direccion{...},telefono,correo,codEstable,
 *         codPuntoVenta,tipoItemExpor,recintoFiscal,tipoRegimen,regimen},
 *  receptor{nombre,tipoDocumento,numDocumento,descActividad,nombreComercial,codPais,nombrePais,complemento,
 *           tipoPersona,telefono,correo},
 *  items[{codigo,descripcion,cantidad,precio,descuento,tipo_item,uni_medida}], seguro, flete,
 *  condicion_operacion, forma_pago, dias_credito, cod_incoterms, desc_incoterms, observaciones
 */
class ExportacionBuilder
{
    public function build(array $ctx): array
    {
        $e = $ctx['emisor'];
        $tipoExpo = (int) ($e['tipoItemExpor'] ?? 0);
        if (! in_array($tipoExpo, [1, 2], true)) {
            throw new \RuntimeException('Tipo de exportación no válido: use 1 (bienes) o 2 (servicios).');
        }
        if ($tipoExpo === 1 && (empty($e['recintoFiscal']) || empty($e['tipoRegimen']) || empty($e['regimen']))) {
            throw new \RuntimeException('Exportación de bienes: indique recinto fiscal, tipo de régimen y régimen de exportación en DTE > Administración.');
        }
        if ($tipoExpo === 2) {
            $e['recintoFiscal'] = $e['tipoRegimen'] = $e['regimen'] = null;
        }

        $items = $ctx['items'] ?? [];
        if ($items === [] || count($items) > 2000) {
            throw new \RuntimeException('La factura de exportación requiere entre 1 y 2000 ítems.');
        }

        $cuerpo = [];
        $gravada = 0.0;
        $descu = 0.0;
        foreach (array_values($items) as $i => $it) {
            $cantidad = round((float) $it['cantidad'], 8);
            $precio = round((float) $it['precio'], 8);
            $dsc = round((float) ($it['descuento'] ?? 0), 8);
            $venta = max(0.0, round($cantidad * $precio - $dsc, 8));
            $gravada += $venta;
            $descu += $dsc;
            $codigo = trim((string) ($it['codigo'] ?? ''));

            $cuerpo[] = [
                'numItem' => $i + 1,
                'tipoItem' => (int) ($it['tipo_item'] ?? $tipoExpo),
                'numeroDocumento' => null,
                'cantidad' => $cantidad,
                'codigo' => $codigo === '' ? null : mb_substr($codigo, 0, 25),
                'codTributo' => null,
                'uniMedida' => (int) ($it['uni_medida'] ?? 59),
                'descripcion' => mb_substr(trim((string) ($it['descripcion'] ?? '')) ?: 'ITEM', 0, 1500),
                'precioUni' => $precio,
                'montoDescu' => $dsc,
                'ventaGravada' => $venta,
                'tributos' => $venta > 0 ? ['C3'] : null,
                'noGravado' => 0.0,
            ];
        }

        $totalGravada = round($gravada, 2);
        $seguro = round((float) ($ctx['seguro'] ?? 0), 2);
        $flete = round((float) ($ctx['flete'] ?? 0), 2);
        $monto = round($totalGravada + $seguro + $flete, 2);
        $pagar = $monto;

        /** @var CarbonInterface $fecha */
        $fecha = $ctx['fecha'];
        $conting = $ctx['contingencia'] ?? null;
        $condicion = $pagar == 0.0 ? 1 : (int) ($ctx['condicion_operacion'] ?? 1);

        return [
            'identificacion' => [
                'version' => 3,
                'ambiente' => (string) $ctx['ambiente'],
                'tipoDte' => '11',
                'numeroControl' => (string) $ctx['numero_control'],
                'codigoGeneracion' => strtoupper((string) $ctx['codigo_generacion']),
                'tipoModelo' => $conting ? 2 : 1,
                'tipoOperacion' => $conting ? 2 : 1,
                'tipoContingencia' => $conting ? (int) $conting['tipo'] : null,
                'motivoContin' => $conting && (int) $conting['tipo'] === 5 ? (string) $conting['motivo'] : null,
                'fecEmi' => $fecha->format('Y-m-d'),
                'horEmi' => $fecha->format('H:i:s'),
                'tipoMoneda' => 'USD',
            ],
            'documentoRelacionado' => null,
            'emisor' => $e,
            'receptor' => $ctx['receptor'],
            'otrosDocumentos' => null,
            'ventaTercero' => null,
            'compraTercero' => null,
            'cuerpoDocumento' => $cuerpo,
            'resumen' => [
                'totalGravada' => $totalGravada,
                'descuGravada' => 0.0,
                'porcentajeDescuento' => 0.0,
                'totalDescu' => round($descu, 2),
                'seguro' => $seguro,
                'flete' => $flete,
                'tributos' => $totalGravada > 0
                    ? [['codigo' => 'C3', 'descripcion' => 'Impuesto al Valor Agregado (exportaciones) 0%', 'valor' => 0.0]]
                    : null,
                'montoTotalOperacion' => $monto,
                'totalNoGravado' => 0.0,
                'totalNoOnerosas' => 0.0,
                'totalPagar' => $pagar,
                'totalLetras' => (new DteV2Builder())->aLetras($pagar),
                'saldoFavor' => 0.0,
                'condicionOperacion' => $condicion,
                'pagos' => $this->pagos($condicion, $pagar, $ctx),
                'codIncoterms' => $ctx['cod_incoterms'] ?? null,
                'descIncoterms' => $ctx['desc_incoterms'] ?? null,
                'numPagoElectronico' => null,
                'observaciones' => ($o = trim((string) ($ctx['observaciones'] ?? ''))) === '' ? null : mb_substr($o, 0, 3000),
            ],
            'apendice' => null,
        ];
    }

    private function pagos(int $condicion, float $pagar, array $ctx): array
    {
        if ($condicion === 2) {
            return [['codigo' => null, 'montoPago' => 0.0, 'referencia' => null, 'plazo' => '01', 'periodo' => max(1, (int) ($ctx['dias_credito'] ?? 1))]];
        }

        return [['codigo' => (string) ($ctx['forma_pago'] ?? '01'), 'montoPago' => $pagar, 'referencia' => null, 'plazo' => null, 'periodo' => null]];
    }
}
