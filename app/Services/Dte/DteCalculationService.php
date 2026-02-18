<?php

namespace App\Services\Dte;

class DteCalculationService
{
    private const IVA_RATE = 0.13;
    private const RET_IVA_RATE = 0.01;
    private const EPSILON = 0.02;

    public function calculate(array $items, array $options = []): array
    {
        $gravadas = 0.0;
        $exentas = 0.0;
        $noSujetas = 0.0;
        $iva = 0.0;
        $descuentoTotal = 0.0;

        $normalizedItems = [];

        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = $this->round2((float) ($item['unit_price'] ?? $item['price'] ?? 0));
            $discount = $this->round2((float) ($item['discount'] ?? 0));
            $tipoItem = (int) ($item['tipo_item'] ?? 1);
            $uniMedida = isset($item['uni_medida']) ? (int) $item['uni_medida'] : null;
            $category = (string) ($item['category'] ?? (($item['afecto_iva'] ?? true) ? 'gravada' : 'exenta'));
            $afectoIva = (bool) ($item['afecto_iva'] ?? true);

            $lineBase = $this->round2(($quantity * $unitPrice) - $discount);
            if ($lineBase < 0) {
                $lineBase = 0.0;
            }

            $montoGravado = 0.0;
            $montoExento = 0.0;
            $montoNoSujeto = 0.0;

            if ($category === 'no_sujeta') {
                $montoNoSujeto = $lineBase;
                $noSujetas += $lineBase;
            } elseif ($category === 'exenta') {
                $montoExento = $lineBase;
                $exentas += $lineBase;
            } else {
                $montoGravado = $lineBase;
                $gravadas += $lineBase;
            }

            $ivaItem = ($montoGravado > 0 && $afectoIva) ? $this->round2($montoGravado * self::IVA_RATE) : 0.0;
            $iva += $ivaItem;
            $descuentoTotal += $discount;

            $normalizedItems[] = [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'quantity' => $quantity,
                'price' => $unitPrice,
                'precio_unitario' => $unitPrice,
                'descuento' => $discount,
                'monto_gravado' => $this->round2($montoGravado),
                'monto_exento' => $this->round2($montoExento),
                'monto_no_sujeto' => $this->round2($montoNoSujeto),
                'iva_item' => $this->round2($ivaItem),
                'total_item' => $this->round2($lineBase + $ivaItem),
                'subtotal' => $this->round2($lineBase),
                'tipo_item' => $tipoItem,
                'uni_medida' => $uniMedida,
            ];
        }

        $retencionIva = ! empty($options['aplica_retencion_iva']) ? $this->round2($gravadas * self::RET_IVA_RATE) : 0.0;
        $retencionRenta = $this->round2((float) ($options['retencion_renta'] ?? 0));
        $subtotal = $this->round2($gravadas + $exentas + $noSujetas);
        $totalPagar = $this->round2($subtotal + $iva - $retencionIva - $retencionRenta);

        $expected = $this->round2(array_sum(array_column($normalizedItems, 'total_item')) - $retencionIva - $retencionRenta);
        if (abs($expected - $totalPagar) > self::EPSILON) {
            throw new \RuntimeException('Descuadre en cálculo fiscal: total no coincide con detalle.');
        }

        return [
            'items' => $normalizedItems,
            'totals' => [
                'gravadas' => $this->round2($gravadas),
                'exentas' => $this->round2($exentas),
                'no_sujetas' => $this->round2($noSujetas),
                'iva' => $this->round2($iva),
                'descuento_total' => $this->round2($descuentoTotal),
                'retencion_iva' => $retencionIva,
                'retencion_renta' => $retencionRenta,
                'subtotal' => $subtotal,
                'total' => $totalPagar,
            ],
        ];
    }

    private function round2(float $value): float
    {
        return round($value, 2);
    }
}
