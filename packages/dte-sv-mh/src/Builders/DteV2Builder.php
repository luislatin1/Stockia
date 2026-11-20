<?php

namespace Stockia\DteSvMh\Builders;

use Carbon\CarbonInterface;

/**
 * Constructor del DTE v2 para Factura (01, JSON v2) y Comprobante de Crédito Fiscal (03, JSON v4).
 *
 * Es una función pura: recibe datos ya normalizados (sin Eloquent, sin base de datos) y devuelve el
 * arreglo listo para validar contra el esquema oficial y firmar. Así se puede probar con miles de
 * ventas históricas sin tocar nada.
 *
 * Reglas aplicadas (Normativa v2.0, 7.2 y Anexo IV):
 *  - Cuerpo con hasta 8 decimales por ítem; Resumen con 2, calculado desde los valores del ítem.
 *  - Factura (01): los precios van CON IVA; `ivaItem` = (ventaGravada / 1.13) * 13 % (informativo) y
 *    el resumen trae `totalIva`. El IVA no se lista como tributo.
 *  - CCF (03): los precios van SIN IVA; cada ítem lleva el tributo ['20'] y el resumen lo detalla.
 *
 * Entrada ($ctx):
 *  tipo_dte 01|03, ambiente 00|01, numero_control, codigo_generacion, fecha (CarbonInterface en hora
 *  de El Salvador), contingencia ?{tipo:int, motivo:string}, emisor{...}, receptor ?{...},
 *  items[{codigo, descripcion, cantidad, precio (SIN IVA), descuento (SIN IVA), categoria
 *  gravada|exenta|no_sujeta, tipo_item, uni_medida}], tasa_iva (0.13), iva_retenido,
 *  condicion_operacion 1|2, forma_pago ('01'…), dias_credito.
 */
class DteV2Builder
{
    public function build(array $ctx): array
    {
        $tipo = (string) $ctx['tipo_dte'];
        if (! in_array($tipo, ['01', '03'], true)) {
            throw new \InvalidArgumentException("El constructor v2 aún no soporta el tipo de DTE {$tipo} (solo 01 y 03).");
        }

        $tasa = (float) ($ctx['tasa_iva'] ?? 0.13);
        $items = $this->cuerpo($tipo, $ctx['items'] ?? [], $tasa);
        $resumen = $this->resumen($tipo, $items, $ctx, $tasa);

        $dte = [
            'identificacion' => $this->identificacion($tipo, $ctx),
            'documentoRelacionado' => null,
            'emisor' => $ctx['emisor'],
            'receptor' => $ctx['receptor'] ?? null,
            'otrosDocumentos' => null,
            'ventaTercero' => null,
            'cuerpoDocumento' => array_map(fn ($i) => $i['linea'], $items),
            'resumen' => $resumen,
            'apendice' => null,
        ];

        return $dte;
    }

    private function identificacion(string $tipo, array $ctx): array
    {
        /** @var CarbonInterface $fecha */
        $fecha = $ctx['fecha'];
        $conting = $ctx['contingencia'] ?? null;

        return [
            'version' => $tipo === '03' ? 4 : 2,
            'ambiente' => (string) $ctx['ambiente'],
            'tipoDte' => $tipo,
            'numeroControl' => (string) $ctx['numero_control'],
            'codigoGeneracion' => strtoupper((string) $ctx['codigo_generacion']),
            // Modelo 1 = facturación previa; operación 1 = transmisión normal, 2 = contingencia.
            'tipoModelo' => $conting ? 2 : 1,
            'tipoOperacion' => $conting ? 2 : 1,
            'tipoContingencia' => $conting ? (int) $conting['tipo'] : null,
            'motivoContin' => $conting ? (string) $conting['motivo'] : null,
            'fecEmi' => $fecha->format('Y-m-d'),
            'horEmi' => $fecha->format('H:i:s'),
            'tipoMoneda' => 'USD',
        ];
    }

    /**
     * @return array<int, array{linea: array, gravada: float, exenta: float, no_suj: float, iva: float, descu: float}>
     */
    private function cuerpo(string $tipo, array $items, float $tasa): array
    {
        if ($items === []) {
            throw new \RuntimeException('El documento no tiene ítems.');
        }
        if (count($items) > 2000) {
            throw new \RuntimeException('Un DTE admite como máximo 2000 ítems.');
        }

        $factor = $tipo === '01' ? 1 + $tasa : 1.0;
        $salida = [];

        foreach (array_values($items) as $i => $it) {
            $cantidad = $this->r8((float) $it['cantidad']);
            $categoria = (string) ($it['categoria'] ?? 'gravada');
            // Solo lo gravado lleva IVA incluido en la Factura; lo exento/no sujeto va tal cual.
            $f = $categoria === 'gravada' ? $factor : 1.0;

            $precio = $this->r8((float) $it['precio'] * $f);
            $descu = $this->r8((float) ($it['descuento'] ?? 0) * $f);
            $venta = max(0.0, $this->r8($cantidad * $precio - $descu));

            $gravada = $categoria === 'gravada' ? $venta : 0.0;
            $exenta = $categoria === 'exenta' ? $venta : 0.0;
            $noSuj = $categoria === 'no_sujeta' ? $venta : 0.0;

            $linea = [
                'numItem' => $i + 1,
                'tipoItem' => (int) ($it['tipo_item'] ?? 1),
                'numeroDocumento' => null,
                'cantidad' => $cantidad,
                'codigo' => $this->texto($it['codigo'] ?? null, 25),
                'codTributo' => null,
                'uniMedida' => (int) ($it['uni_medida'] ?? 59),
                'descripcion' => $this->texto($it['descripcion'] ?? null, 1500) ?? 'ITEM',
                'precioUni' => $precio,
                'montoDescu' => $descu,
                'ventaNoSuj' => $noSuj,
                'ventaExenta' => $exenta,
                'ventaGravada' => $gravada,
                'tributos' => $tipo === '03' && $gravada > 0 ? ['20'] : null,
                'psv' => 0.0,
                'noGravado' => 0.0,
            ];

            $iva = 0.0;
            if ($tipo === '01') {
                $iva = $gravada > 0 ? $this->r8($gravada / (1 + $tasa) * $tasa) : 0.0;
                $linea['ivaItem'] = $iva;
            }

            $salida[] = ['linea' => $linea, 'gravada' => $gravada, 'exenta' => $exenta, 'no_suj' => $noSuj, 'iva' => $iva, 'descu' => $descu];
        }

        return $salida;
    }

    private function resumen(string $tipo, array $items, array $ctx, float $tasa): array
    {
        $totalNoSuj = $this->r2(array_sum(array_column($items, 'no_suj')));
        $totalExenta = $this->r2(array_sum(array_column($items, 'exenta')));
        $totalGravada = $this->r2(array_sum(array_column($items, 'gravada')));
        $totalDescu = $this->r2(array_sum(array_column($items, 'descu')));

        $subTotalVentas = $this->r2($totalNoSuj + $totalExenta + $totalGravada);
        $subTotal = $subTotalVentas; // sin descuentos globales: Stockia descuenta por ítem
        $ivaRete = $this->r2((float) ($ctx['iva_retenido'] ?? 0));

        $resumen = [
            'totalNoSuj' => $totalNoSuj,
            'totalExenta' => $totalExenta,
            'totalGravada' => $totalGravada,
            'subTotalVentas' => $subTotalVentas,
            'descuNoSuj' => 0.0,
            'descuExenta' => 0.0,
            'descuGravada' => 0.0,
            'porcentajeDescuento' => 0.0,
            'totalDescu' => $totalDescu,
            'tributos' => null,
            'subTotal' => $subTotal,
        ];

        if ($tipo === '03') {
            $tributo = $totalGravada > 0 ? $this->r2($totalGravada * $tasa) : 0.0;
            $resumen['tributos'] = $totalGravada > 0
                ? [['codigo' => '20', 'descripcion' => 'Impuesto al Valor Agregado 13%', 'valor' => $tributo]]
                : null;
            $resumen['ivaPerci'] = 0.0;
            $resumen['ivaRete'] = $ivaRete;
            $monto = $this->r2($subTotal + $tributo);
            $pagar = max(0.0, $this->r2($monto + 0.0 - $ivaRete));
        } else {
            $resumen['ivaRete'] = $ivaRete;
            $monto = $subTotal; // el IVA ya va incluido en las ventas gravadas
            $pagar = max(0.0, $this->r2($monto - $ivaRete));
        }

        $resumen['montoTotalOperacion'] = $monto;
        $resumen['totalNoGravado'] = 0.0;
        $resumen['totalPagar'] = $pagar;
        $resumen['totalLetras'] = $this->aLetras($pagar);

        if ($tipo === '01') {
            $resumen['totalIva'] = $this->r2(array_sum(array_column($items, 'iva')));
        }

        $resumen['saldoFavor'] = 0.0;
        $resumen['condicionOperacion'] = $pagar == 0.0 ? 1 : (int) ($ctx['condicion_operacion'] ?? 1);
        $resumen['pagos'] = $this->pagos($resumen['condicionOperacion'], $pagar, $ctx);
        $resumen['numPagoElectronico'] = null;
        $resumen['observaciones'] = null;

        return $resumen;
    }

    private function pagos(int $condicion, float $pagar, array $ctx): array
    {
        if ($condicion === 2) {
            // A crédito: sin forma de pago, monto 0 y plazo obligatorio (CAT-018: 01 días).
            return [[
                'codigo' => null,
                'montoPago' => 0.0,
                'referencia' => null,
                'plazo' => '01',
                'periodo' => max(1, (int) ($ctx['dias_credito'] ?? 1)),
            ]];
        }

        return [[
            'codigo' => (string) ($ctx['forma_pago'] ?? '01'),
            'montoPago' => $pagar,
            'referencia' => null,
            'plazo' => null,
            'periodo' => null,
        ]];
    }

    /** Monto en letras para `totalLetras`, p. ej. "CIENTO TRECE 00/100 DÓLARES". */
    public function aLetras(float $monto): string
    {
        $monto = round($monto, 2);
        $entero = (int) floor($monto);
        $centavos = (int) round(($monto - $entero) * 100);

        $palabras = $entero === 0 ? 'CERO' : $this->enteroALetras($entero);

        return sprintf('%s %02d/100 DÓLARES', $palabras, $centavos);
    }

    private function enteroALetras(int $n): string
    {
        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE',
            'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE',
            'VEINTIÚN', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE',
            'VEINTIOCHO', 'VEINTINUEVE'];
        $decenas = [3 => 'TREINTA', 4 => 'CUARENTA', 5 => 'CINCUENTA', 6 => 'SESENTA', 7 => 'SETENTA', 8 => 'OCHENTA', 9 => 'NOVENTA'];
        $centenas = [1 => 'CIENTO', 2 => 'DOSCIENTOS', 3 => 'TRESCIENTOS', 4 => 'CUATROCIENTOS', 5 => 'QUINIENTOS',
            6 => 'SEISCIENTOS', 7 => 'SETECIENTOS', 8 => 'OCHOCIENTOS', 9 => 'NOVECIENTOS'];

        $menorMil = function (int $x) use ($unidades, $decenas, $centenas): string {
            if ($x === 100) {
                return 'CIEN';
            }
            $partes = [];
            if ($x >= 100) {
                $partes[] = $centenas[intdiv($x, 100)];
                $x %= 100;
            }
            if ($x >= 30) {
                $d = $decenas[intdiv($x, 10)];
                $u = $x % 10;
                $partes[] = $u ? "{$d} Y {$unidades[$u]}" : $d;
            } elseif ($x > 0) {
                $partes[] = $unidades[$x];
            }

            return implode(' ', $partes);
        };

        if ($n >= 1_000_000_000) {
            return trim($this->enteroALetras(intdiv($n, 1_000_000_000)).' MIL MILLONES '.($n % 1_000_000_000 ? $this->enteroALetras($n % 1_000_000_000) : ''));
        }
        if ($n >= 1_000_000) {
            $mill = intdiv($n, 1_000_000);
            $resto = $n % 1_000_000;

            return trim(($mill === 1 ? 'UN MILLÓN' : $this->enteroALetras($mill).' MILLONES').' '.($resto ? $this->enteroALetras($resto) : ''));
        }
        if ($n >= 1000) {
            $mil = intdiv($n, 1000);
            $resto = $n % 1000;
            $pref = $mil === 1 ? 'MIL' : $menorMil($mil).' MIL';

            return trim($pref.' '.($resto ? $menorMil($resto) : ''));
        }

        return $menorMil($n);
    }

    private function texto(mixed $valor, int $max): ?string
    {
        $t = trim((string) $valor);

        return $t === '' ? null : mb_substr($t, 0, $max);
    }

    /** Redondeo de Cuerpo: 8 decimales, 9.º ≥ 5 sube (Normativa 7.2 a). */
    private function r8(float $v): float
    {
        return round($v, 8);
    }

    /** Redondeo de Resumen: 2 decimales (Normativa 7.2 b). */
    private function r2(float $v): float
    {
        return round($v, 2);
    }
}
