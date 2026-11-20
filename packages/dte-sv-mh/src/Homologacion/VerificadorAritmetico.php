<?php

namespace Stockia\DteSvMh\Homologacion;

/**
 * Verificaciones de cálculo que Hacienda ejecuta además del esquema (Normativa v2.0 Anexo IV; Manual §XXI):
 * cada monto del cuerpo contra precio × cantidad − descuento, y cada total del resumen contra la suma de los
 * ítems, con holgura de ±0,01.
 *
 * Es independiente de los constructores a propósito: recalcula desde los campos ya escritos en el JSON, así que
 * detecta un error de cálculo del constructor aunque el documento cumpla el esquema.
 */
final class VerificadorAritmetico
{
    private const HOLGURA = 0.0101;

    /** @return string[] mensajes de error; vacío si todo cuadra */
    public static function verificar(array $dte, string $clave): array
    {
        $e = [];
        $cuerpo = $dte['cuerpoDocumento'] ?? [];
        $r = $dte['resumen'] ?? [];

        if (in_array($clave, ['invalidacion'], true) || ! $cuerpo || ! $r) {
            return $e;
        }

        $igual = function (string $que, float $a, float $b) use (&$e): void {
            if (abs($a - $b) > self::HOLGURA) {
                $e[] = sprintf('%s: %.2f ≠ %.2f', $que, $a, $b);
            }
        };

        // ---- por ítem ----
        $sumas = ['noSuj' => 0.0, 'exenta' => 0.0, 'gravada' => 0.0, 'iva' => 0.0, 'compra' => 0.0, 'descu' => 0.0];
        foreach ($cuerpo as $i => $it) {
            $n = $i + 1;
            $base = (float) $it['cantidad'] * (float) $it['precioUni'] - (float) ($it['montoDescu'] ?? 0);

            if ($clave === '14') {
                $igual("ítem {$n} compra", (float) $it['compra'], $base);
                $sumas['compra'] += (float) $it['compra'];
            } elseif ($clave === 'retorno') {
                $igual("ítem {$n} ventas", (float) $it['ventaNoSuj'] + (float) $it['ventaExenta'] + (float) $it['ventaGravada'], $base);
            } else {
                $igual("ítem {$n} ventas", (float) ($it['ventaNoSuj'] ?? 0) + (float) ($it['ventaExenta'] ?? 0) + (float) $it['ventaGravada'], $base);
            }
            $sumas['noSuj'] += (float) ($it['ventaNoSuj'] ?? 0);
            $sumas['exenta'] += (float) ($it['ventaExenta'] ?? 0);
            $sumas['gravada'] += (float) ($it['ventaGravada'] ?? 0);
            $sumas['iva'] += (float) ($it['ivaItem'] ?? $it['totalIva'] ?? 0);
            $sumas['descu'] += (float) ($it['montoDescu'] ?? 0);

            // Factura: IVA incluido en lo gravado = gravada / 1,13 × 0,13. CCF/NC/ND: IVA = gravada × 0,13.
            if (in_array($clave, ['01', 'retorno'], true) && (float) $it['ventaGravada'] > 0) {
                $igual("ítem {$n} IVA", (float) $it['ivaItem'], (float) $it['ventaGravada'] / 1.13 * 0.13);
            }
            if (in_array($clave, ['05', '06'], true) && (float) $it['ventaGravada'] > 0) {
                $igual("ítem {$n} IVA", (float) $it['totalIva'], (float) $it['ventaGravada'] * 0.13);
            }
        }

        // ---- resumen ----
        if ($clave === '14') {
            $igual('totalCompra', (float) $r['totalCompra'], $sumas['compra']);
            $igual('subTotal', (float) $r['subTotal'], (float) $r['totalCompra'] - (float) $r['descu']);
            $igual('totalPagar', (float) $r['totalPagar'], (float) $r['subTotal'] - (float) $r['reteRenta']);

            return $e;
        }

        $igual('totalNoSuj', (float) ($r['totalNoSuj'] ?? 0), $sumas['noSuj']);
        $igual('totalExenta', (float) ($r['totalExenta'] ?? 0), $sumas['exenta']);
        $igual('totalGravada', (float) $r['totalGravada'], $sumas['gravada']);
        if (isset($r['totalDescu'])) { // el Evento de Retorno no lo trae
            $igual('totalDescu', (float) $r['totalDescu'], $sumas['descu']);
        }

        if ($clave === '11') {
            $igual('monto total', (float) $r['montoTotalOperacion'], (float) $r['totalGravada'] - (float) $r['descuGravada'] + (float) $r['seguro'] + (float) $r['flete']);
            $igual('totalPagar', (float) $r['totalPagar'], (float) $r['montoTotalOperacion'] + (float) $r['totalNoGravado']);
            if ((float) ($r['tributos'][0]['valor'] ?? 0) !== 0.0) {
                $e[] = 'tributo C3 de exportación debe ser 0.00';
            }

            return $e;
        }

        $igual('subTotalVentas', (float) $r['subTotalVentas'], (float) $r['totalNoSuj'] + (float) $r['totalExenta'] + (float) $r['totalGravada']);

        if (in_array($clave, ['01', 'retorno'], true)) {
            $igual('totalIva', (float) $r['totalIva'], $sumas['iva']);
            $igual('monto total', (float) $r['montoTotalOperacion'], (float) $r['subTotalVentas']);
            $igual('totalPagar', (float) $r['totalPagar'], (float) $r['montoTotalOperacion'] - (float) $r['ivaRete']);
        } else { // 03, 05, 06
            $iva = (float) $r['totalGravada'] * 0.13;
            $valor = (float) ($r['tributos'][0]['valor'] ?? 0);
            $igual('tributo 20', $valor, $iva);
            $igual('monto total', (float) $r['montoTotalOperacion'], (float) $r['subTotalVentas'] + $valor);
            $igual('totalPagar', (float) $r['totalPagar'], (float) $r['montoTotalOperacion'] + (float) ($r['ivaPerci'] ?? 0) - (float) $r['ivaRete'] + (float) $r['totalNoGravado']);
            if (in_array($clave, ['05', '06'], true)) {
                $igual('totalIva', (float) $r['totalIva'], $sumas['iva']);
            }
        }

        return $e;
    }
}
