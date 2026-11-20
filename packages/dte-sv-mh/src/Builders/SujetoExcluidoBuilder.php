<?php

namespace Stockia\DteSvMh\Builders;

use Carbon\CarbonInterface;

/**
 * Factura de Sujeto Excluido (14, esquema fe-fse-v2): la emite quien COMPRA a una persona que no es
 * contribuyente de IVA. El "receptor" del esquema es el sujeto excluido (el vendedor).
 *
 * No hay IVA: el cuerpo trae solo `compra` por ítem (cantidad × precio − descuento, 8 decimales) y el resumen
 * calcula totalCompra, subTotal, retención de renta (valor dado) y totalPagar = subTotal − reteRenta
 * (Normativa Anexo IV: "Sub total − Retención Renta").
 *
 * Entrada ($ctx): ambiente, numero_control, codigo_generacion, fecha, contingencia,
 *  emisor{nit,nrc,nombre,codActividad,descActividad,direccion{...},telefono,correo,codEstable,codPuntoVenta},
 *  receptor{tipoDocumento,numDocumento,nombre,codActividad,descActividad,direccion{departamento,municipio,distrito,complemento},telefono,correo},
 *  items[{codigo,descripcion,cantidad,precio,descuento,tipo_item,uni_medida}], retencion_renta,
 *  condicion_operacion, forma_pago, dias_credito, observaciones
 */
class SujetoExcluidoBuilder
{
    public function build(array $ctx): array
    {
        $items = $ctx['items'] ?? [];
        if ($items === [] || count($items) > 2000) {
            throw new \RuntimeException('La factura de sujeto excluido requiere entre 1 y 2000 ítems.');
        }

        $cuerpo = [];
        $total = 0.0;
        $descu = 0.0;
        foreach (array_values($items) as $i => $it) {
            $cantidad = round((float) $it['cantidad'], 8);
            $precio = round((float) $it['precio'], 8);
            $dsc = round((float) ($it['descuento'] ?? 0), 8);
            $compra = max(0.0, round($cantidad * $precio - $dsc, 8));
            $total += $compra;
            $descu += $dsc;
            $codigo = trim((string) ($it['codigo'] ?? ''));

            $cuerpo[] = [
                'numItem' => $i + 1,
                'tipoItem' => (int) ($it['tipo_item'] ?? 1),
                'cantidad' => $cantidad,
                'codigo' => $codigo === '' ? null : mb_substr($codigo, 0, 25),
                'uniMedida' => (int) ($it['uni_medida'] ?? 59),
                'descripcion' => mb_substr(trim((string) ($it['descripcion'] ?? '')) ?: 'ITEM', 0, 1500),
                'precioUni' => $precio,
                'montoDescu' => $dsc,
                'compra' => $compra,
            ];
        }

        $totalCompra = round($total, 2);
        $subTotal = $totalCompra;
        $renta = round((float) ($ctx['retencion_renta'] ?? 0), 2);
        if ($renta > $subTotal) {
            throw new \RuntimeException('La retención de renta no puede superar el total de la compra.');
        }
        $pagar = round($subTotal - $renta, 2);

        /** @var CarbonInterface $fecha */
        $fecha = $ctx['fecha'];
        $conting = $ctx['contingencia'] ?? null;
        $condicion = $pagar == 0.0 ? 1 : (int) ($ctx['condicion_operacion'] ?? 1);

        return [
            'identificacion' => [
                'version' => 2,
                'ambiente' => (string) $ctx['ambiente'],
                'tipoDte' => '14',
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
            'emisor' => $ctx['emisor'],
            'receptor' => $ctx['receptor'],
            'cuerpoDocumento' => $cuerpo,
            'resumen' => [
                'totalCompra' => $totalCompra,
                'descu' => 0.0,
                'totalDescu' => round($descu, 2),
                'subTotal' => $subTotal,
                'reteRenta' => $renta,
                'totalPagar' => $pagar,
                'totalLetras' => (new DteV2Builder())->aLetras($pagar),
                'condicionOperacion' => $condicion,
                'pagos' => $this->pagos($condicion, $pagar, $ctx),
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
