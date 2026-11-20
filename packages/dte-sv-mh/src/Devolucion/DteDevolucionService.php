<?php

namespace Stockia\DteSvMh\Devolucion;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Stockia\DteSvMh\Models\Dte;
use Stockia\DteSvMh\Services\DteEmissionService;

/**
 * Orquesta lo que se le informa a Hacienda cuando se devuelve o ajusta una venta con DTE
 * (ver RutaDevolucion). Se invoca desde el ajuste administrativo de ventas DENTRO de su
 * transacción: si Hacienda rechaza o algo falla, se lanza una excepción y todo se revierte.
 */
class DteDevolucionService
{
    /**
     * Motivos internos de una Nota de Débito. Hacienda no define una lista (el esquema no trae campo de motivo):
     * quedan en `observaciones`. Una ND aumenta el valor de un CCF ya emitido.
     */
    public const MOTIVOS_DEBITO = [
        'cargo_adicional' => 'Cargo adicional (flete, gastos)',
        'intereses_mora' => 'Intereses por mora',
        'precio_menor' => 'Diferencia de precio a favor del vendedor',
        'otro_cargo' => 'Otro cargo',
    ];

    public function __construct(private readonly DteEmissionService $emision)
    {
    }

    /** Tipos de documento propios que se pueden invalidar desde la pantalla de la venta. */
    public const INVALIDABLES = ['05', '06', '18'];

    /** ¿Se puede invalidar este documento desde la pantalla de la venta? */
    public function esInvalidable(Dte $dte): bool
    {
        return in_array((string) $dte->tipo_dte, self::INVALIDABLES, true) && (string) $dte->estado === 'ACEPTADO';
    }

    /** Otras Notas de Débito aceptadas de la misma venta (candidatas a reemplazar una ND errónea). */
    public function reemplazosDeDebito(Dte $nd): \Illuminate\Support\Collection
    {
        return Dte::withoutGlobalScopes()->where('sale_id', $nd->sale_id)->where('tipo_dte', '06')
            ->where('estado', 'ACEPTADO')->where('id', '!=', $nd->id)->orderBy('id')->get();
    }

    /**
     * Invalida una Nota de Crédito, una Nota de Débito o un Evento de Retorno (Manual Funcional v2.0 §VII).
     *  - NC y Evento de Retorno: el código de reemplazo va siempre null; si el error era de la nota se emite una nueva después.
     *  - ND: con tipo 1 o 3 hay que emitir ANTES la ND correcta y pasarla como reemplazo; con tipo 2 (rescindir) no.
     * Plazo: décimo día hábil del mes siguiente al sello. No revierte inventario ni caja (solo el documento ante Hacienda);
     * sí devuelve al cliente de una venta a crédito el cargo de una ND invalidada.
     */
    public function invalidarDocumento(Dte $dte, string $tipoInvalidacion, string $motivo, ?int $dteReemplazoId = null): void
    {
        if (! $this->esInvalidable($dte)) {
            throw new \RuntimeException('Solo se pueden invalidar aquí Notas de Crédito, Notas de Débito y Eventos de Retorno aceptados por Hacienda.');
        }
        if (! in_array($tipoInvalidacion, ['1', '2', '3'], true)) {
            throw new \RuntimeException('Tipo de invalidación no válido.');
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new \RuntimeException('Indique el motivo de la invalidación.');
        }

        $zona = config('dte.timezone', 'America/El_Salvador');
        $sello = $dte->fecha_envio ?? $dte->created_at;
        if (! PlazoInvalidacion::vigente((string) $dte->tipo_dte, $sello, null, $zona)) {
            $limite = PlazoInvalidacion::limite((string) $dte->tipo_dte, $sello, $zona)->format('d/m/Y');
            throw new \RuntimeException("Venció el plazo para invalidar este documento ({$limite}). Consulte con el contador.");
        }

        $reemplazo = null;
        if ((string) $dte->tipo_dte === '06' && $tipoInvalidacion !== '2') {
            $reemplazo = $dteReemplazoId
                ? $this->reemplazosDeDebito($dte)->firstWhere('id', $dteReemplazoId)
                : null;
            if (! $reemplazo) {
                throw new \RuntimeException('Con este tipo de invalidación Hacienda exige emitir primero la Nota de Débito correcta (aceptada) y seleccionarla como reemplazo.');
            }
        }

        $this->emision->invalidate($dte, $tipoInvalidacion, $motivo, $reemplazo?->codigo_generacion);

        // Una ND a crédito aumentó la deuda del cliente: al invalidarla, deja de deberse.
        if ((string) $dte->tipo_dte === '06' && $dte->sale_id) {
            $sale = DB::table('sales')->where('id', $dte->sale_id)->first(['payment_method', 'saldo_pendiente']);
            if ($sale && $sale->payment_method === 'credit') {
                $monto = min((float) $sale->saldo_pendiente, (float) ($dte->json_original['resumen']['totalPagar'] ?? 0));
                if ($monto > 0) {
                    DB::table('sales')->where('id', $dte->sale_id)->decrement('saldo_pendiente', $monto);
                }
            }
        }
    }

    /** Factura de Sujeto Excluido vigente de una compra (la que no está rechazada ni invalidada). */
    public function fseDeCompra(\App\Models\Purchase $purchase): ?Dte
    {
        return Dte::withoutGlobalScopes()->where('purchase_id', $purchase->id)->where('tipo_dte', '14')
            ->whereNotIn('estado', ['RECHAZADO', 'INVALIDADO'])->latest('id')->first();
    }

    /**
     * Invalida la Factura de Sujeto Excluido de una compra (plazo: 3 meses desde el sello). Con tipo 1 o 3 hay que
     * emitir antes la factura correcta (de otra compra aceptada) y pasarla como reemplazo; con tipo 2 no.
     */
    public function invalidarSujetoExcluido(\App\Models\Purchase $purchase, string $tipoInvalidacion, string $motivo, ?int $compraReemplazoId = null): void
    {
        $fse = $this->fseDeCompra($purchase);
        if (! $fse || (string) $fse->estado !== 'ACEPTADO') {
            throw new \RuntimeException('La compra no tiene una Factura de Sujeto Excluido aceptada por Hacienda.');
        }
        if (! in_array($tipoInvalidacion, ['1', '2', '3'], true)) {
            throw new \RuntimeException('Tipo de invalidación no válido.');
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new \RuntimeException('Indique el motivo de la invalidación.');
        }

        $zona = config('dte.timezone', 'America/El_Salvador');
        $sello = $fse->fecha_envio ?? $fse->created_at;
        if (! PlazoInvalidacion::vigente('14', $sello, null, $zona)) {
            $limite = PlazoInvalidacion::limite('14', $sello, $zona)->format('d/m/Y');
            throw new \RuntimeException("Venció el plazo para invalidar esta factura ({$limite}). Consulte con el contador.");
        }

        $reemplazo = null;
        if ($tipoInvalidacion !== '2') {
            $reemplazo = $compraReemplazoId && $compraReemplazoId !== (int) $purchase->id
                ? Dte::withoutGlobalScopes()->where('purchase_id', $compraReemplazoId)->where('company_id', $purchase->company_id)
                    ->where('tipo_dte', '14')->where('estado', 'ACEPTADO')->latest('id')->first()
                : null;
            if (! $reemplazo) {
                throw new \RuntimeException('Con este tipo de invalidación Hacienda exige emitir antes la factura correcta (otra compra con su Factura de Sujeto Excluido aceptada) y elegirla como reemplazo.');
            }
        }

        $this->emision->invalidate($fse, $tipoInvalidacion, $motivo, $reemplazo?->codigo_generacion);
    }

    /** ¿Se puede emitir una Nota de Débito sobre esta venta? Devuelve el CCF aceptado o null. */
    public function ccfParaDebito(Sale $sale): ?Dte
    {
        $origen = $this->origenDe($sale);

        return $origen && (string) $origen->tipo_dte === '03' && (string) $origen->estado === 'ACEPTADO' ? $origen : null;
    }

    /**
     * Emite la Nota de Débito de un CCF por cargos adicionales. Se llama dentro de la transacción del
     * controlador: si Hacienda no la acepta se lanza y se revierte todo.
     *
     * @param  array<int, array{descripcion:string, monto_neto:float, categoria?:string}>  $lineas
     */
    public function emitirDebito(Sale $sale, int $ajusteId, string $motivo, array $lineas, string $notas): Dte
    {
        if (! isset(self::MOTIVOS_DEBITO[$motivo])) {
            throw new \RuntimeException('Motivo de nota de débito no reconocido.');
        }
        $origen = $this->ccfParaDebito($sale);
        if (! $origen) {
            throw new \RuntimeException('La Nota de Débito solo se emite sobre un Comprobante de Crédito Fiscal ya aceptado por Hacienda.');
        }

        $obs = trim(self::MOTIVOS_DEBITO[$motivo]." - venta #{$sale->id}".($notas !== '' ? ": {$notas}" : ''));
        $nota = $this->emision->emitirNotaDebito($sale, $origen, $lineas, $obs, $ajusteId);

        if (! in_array((string) $nota->estado, ['ACEPTADO', 'CONTINGENCIA'], true)) {
            throw new \RuntimeException('Hacienda no aceptó la Nota de Débito (estado '.$nota->estado.'). No se aplicó el cargo.');
        }

        // Venta a crédito: el cargo (con IVA, menos retención) aumenta lo que el cliente debe.
        if ($sale->payment_method === 'credit') {
            DB::table('sales')->where('id', $sale->id)->increment('saldo_pendiente', (float) $nota->json_original['resumen']['totalPagar']);
        }

        DB::table('sale_adjustments')->where('id', $ajusteId)->update([
            'motivo' => $motivo, 'dte_ruta' => 'nota_debito', 'dte_id' => $nota->id, 'updated_at' => now(),
        ]);

        return $nota;
    }

    /** DTE original de la venta (Factura/CCF), nunca una nota. */
    public function origenDe(Sale $sale): ?Dte
    {
        return Dte::withoutGlobalScopes()
            ->where('sale_id', $sale->id)
            ->whereNull('dte_origen_id')
            ->whereIn('tipo_dte', ['01', '03'])
            ->latest('id')
            ->first();
    }

    /**
     * Qué pasará con la venta: ruta y requisitos, sin ejecutar nada.
     *
     * @return array{ruta:string, tipo_invalidacion:?string, requiere_reemplazo:bool, mensaje:?string, origen:?Dte}
     */
    public function planificar(Sale $sale, string $motivo, string $alcance): array
    {
        $origen = $this->origenDe($sale);

        // Sin DTE aceptado ante Hacienda no hay nada que informar (ticket, rechazado, ya invalidado).
        if (! $origen || in_array((string) $origen->estado, ['RECHAZADO', 'INVALIDADO'], true)) {
            return RutaDevolucion::resolver(null, $motivo, $alcance) + ['origen' => null];
        }

        if ((string) $origen->estado !== 'ACEPTADO') {
            return [
                'ruta' => RutaDevolucion::BLOQUEADO, 'tipo_invalidacion' => null, 'requiere_reemplazo' => false, 'origen' => $origen,
                'mensaje' => "El DTE de esta venta está en estado {$origen->estado}: todavía no tiene sello de Hacienda. Reenvíelo (contingencia) o espere antes de devolver.",
            ];
        }

        return RutaDevolucion::resolver((string) $origen->tipo_dte, $motivo, $alcance, $this->retornosVigentes($origen) > 0) + ['origen' => $origen];
    }

    /** Eventos de Retorno de la Factura que siguen vigentes (no rechazados ni invalidados). */
    private function retornosVigentes(Dte $origen): int
    {
        return Dte::withoutGlobalScopes()->where('dte_origen_id', $origen->id)->where('tipo_dte', '18')
            ->whereIn('estado', ['ACEPTADO', 'CONTINGENCIA', 'PENDIENTE'])->count();
    }

    /** total | parcial | valor, según la acción pedida y lo ya devuelto antes. */
    public function alcance(Sale $sale, string $accion, string $motivo, array $filas, ?int $ajusteActual = null): string
    {
        if (RutaDevolucion::esAjusteDeValor($motivo) && $accion === 'return_items') {
            return RutaDevolucion::ALCANCE_VALOR;
        }
        if ($accion !== 'return_items') {
            return RutaDevolucion::ALCANCE_TOTAL;
        }

        $sale->loadMissing('items');
        $devuelto = [];
        foreach ($filas as $f) {
            $devuelto[(int) $f['sale_item_id']] = ($devuelto[(int) $f['sale_item_id']] ?? 0) + (int) $f['quantity'];
        }
        foreach ($sale->items as $item) {
            $resta = (int) $item->quantity - $this->yaDevuelto((int) $item->id, $ajusteActual);
            if ($resta > 0 && ($devuelto[(int) $item->id] ?? 0) < $resta) {
                return RutaDevolucion::ALCANCE_PARCIAL;
            }
        }

        return RutaDevolucion::ALCANCE_TOTAL;
    }

    public function yaDevuelto(int $saleItemId, ?int $excluirAjuste = null): int
    {
        return (int) DB::table('sale_adjustment_items')
            ->where('sale_item_id', $saleItemId)
            ->when($excluirAjuste, fn ($q) => $q->where('sale_adjustment_id', '!=', $excluirAjuste))
            ->sum('quantity');
    }

    /**
     * Ejecuta la ruta ya planificada.
     *
     * @param  array<int, array{sale_item_id:int, quantity:int, credit_amount?:float}>  $filas  líneas del ajuste
     * @return array{ruta:string, dte:?Dte}
     */
    public function ejecutar(Sale $sale, int $ajusteId, string $motivo, string $alcance, string $accion, array $filas, ?int $ventaReemplazoId, string $notas): array
    {
        $plan = $this->planificar($sale, $motivo, $alcance);

        if ($plan['ruta'] === RutaDevolucion::BLOQUEADO) {
            throw new \RuntimeException($plan['mensaje']);
        }
        if ($plan['ruta'] === RutaDevolucion::NINGUNA) {
            return ['ruta' => RutaDevolucion::NINGUNA, 'dte' => null];
        }

        /** @var Dte $origen */
        $origen = $plan['origen'];

        $dte = match ($plan['ruta']) {
            RutaDevolucion::NOTA_CREDITO => $this->notaDeCredito($sale, $origen, $ajusteId, $motivo, $alcance, $accion, $filas, $notas),
            RutaDevolucion::EVENTO_RETORNO => $this->eventoDeRetorno($sale, $origen, $ajusteId, $accion, $filas),
            default => $this->invalidar($sale, $origen, $plan, $motivo, $ventaReemplazoId, $notas),
        };

        DB::table('sale_adjustments')->where('id', $ajusteId)->update([
            'motivo' => $motivo,
            'dte_ruta' => $plan['ruta'],
            'dte_id' => $dte?->id,
            'replacement_sale_id' => $ventaReemplazoId,
            'updated_at' => now(),
        ]);

        return ['ruta' => $plan['ruta'], 'dte' => $dte];
    }

    private function notaDeCredito(Sale $sale, Dte $origen, int $ajusteId, string $motivo, string $alcance, string $accion, array $filas, string $notas): Dte
    {
        $sale->loadMissing('items');
        $items = $sale->items->keyBy('id');
        $neto = fn ($i) => round((float) $i->monto_gravado + (float) $i->monto_exento + (float) $i->monto_no_sujeto, 2);
        $lineas = [];

        if (RutaDevolucion::esAjusteDeValor($motivo)) {
            foreach ($filas as $f) {
                $monto = round((float) ($f['credit_amount'] ?? 0), 2);
                $item = $items->get((int) $f['sale_item_id']);
                if (! $item || $monto <= 0) {
                    continue;
                }
                $acreditado = (float) DB::table('sale_adjustment_items')
                    ->where('sale_item_id', $item->id)->where('sale_adjustment_id', '!=', $ajusteId)->sum('credit_amount');
                if ($monto > round($neto($item) - $acreditado, 2) + 0.001) {
                    throw new \RuntimeException("El monto a acreditar de '{$item->product?->name}' supera lo que queda de la línea (sin IVA).");
                }
                $lineas[] = ['sale_item_id' => $item->id, 'cantidad' => (float) $item->quantity, 'monto_neto' => $monto];
            }
        } elseif ($accion === 'return_items') {
            foreach ($filas as $f) {
                $item = $items->get((int) $f['sale_item_id']);
                $cant = (int) $f['quantity'];
                if (! $item || $cant <= 0) {
                    continue;
                }
                $this->verificarRestante($item, $cant, $ajusteId);
                $lineas[] = ['sale_item_id' => $item->id, 'cantidad' => (float) $cant, 'monto_neto' => round($neto($item) / (float) $item->quantity * $cant, 2)];
            }
        } else {
            // Anular/cancelar la venta completa: se acredita todo lo que aún no se devolvió.
            foreach ($items as $item) {
                $resta = (int) $item->quantity - $this->yaDevuelto((int) $item->id, $ajusteId);
                if ($resta > 0) {
                    $lineas[] = ['sale_item_id' => $item->id, 'cantidad' => (float) $resta, 'monto_neto' => round($neto($item) / (float) $item->quantity * $resta, 2)];
                }
            }
        }

        if ($lineas === []) {
            throw new \RuntimeException('No hay nada que acreditar: indique cantidades devueltas o montos a acreditar.');
        }

        $obs = trim(RutaDevolucion::etiqueta($motivo)." - venta #{$sale->id}".($notas !== '' ? ": {$notas}" : ''));
        $nota = $this->emision->emitirNotaCredito($sale, $origen, $lineas, $obs, $ajusteId);

        if (! in_array((string) $nota->estado, ['ACEPTADO', 'CONTINGENCIA'], true)) {
            throw new \RuntimeException('Hacienda no aceptó la Nota de Crédito (estado '.$nota->estado.'). No se aplicó la devolución.');
        }

        return $nota;
    }

    private function eventoDeRetorno(Sale $sale, Dte $origen, int $ajusteId, string $accion, array $filas): Dte
    {
        $zona = config('dte.timezone', 'America/El_Salvador');
        $orig = $origen->json_original;
        if (! PlazoRetorno::vigente((string) $orig['identificacion']['fecEmi'], $orig['emisor']['codActividad'] ?? null, null, $zona)) {
            $limite = PlazoRetorno::limite((string) $orig['identificacion']['fecEmi'], $orig['emisor']['codActividad'] ?? null, $zona)->format('d/m/Y');
            throw new \RuntimeException("Venció el plazo para aplicar un Evento de Retorno a esta factura ({$limite}).");
        }

        $sale->loadMissing('items');
        $items = $sale->items->keyBy('id');
        $neto = fn ($i) => round((float) $i->monto_gravado + (float) $i->monto_exento + (float) $i->monto_no_sujeto, 2);
        $lineas = [];

        if ($accion === 'return_items') {
            foreach ($filas as $f) {
                $item = $items->get((int) $f['sale_item_id']);
                $cant = (int) $f['quantity'];
                if (! $item || $cant <= 0) {
                    continue;
                }
                $this->verificarRestante($item, $cant, $ajusteId);
                $lineas[] = ['sale_item_id' => $item->id, 'cantidad' => (float) $cant, 'monto_neto' => round($neto($item) / (float) $item->quantity * $cant, 2)];
            }
        } else {
            // Anular o cancelar con retornos previos: se retorna lo que aún no se había devuelto.
            foreach ($items as $item) {
                $resta = (int) $item->quantity - $this->yaDevuelto((int) $item->id, $ajusteId);
                if ($resta > 0) {
                    $lineas[] = ['sale_item_id' => $item->id, 'cantidad' => (float) $resta, 'monto_neto' => round($neto($item) / (float) $item->quantity * $resta, 2)];
                }
            }
        }

        if ($lineas === []) {
            throw new \RuntimeException('No hay nada que retornar: indique cantidades devueltas.');
        }

        $evento = $this->emision->emitirEventoRetorno($sale, $origen, $lineas, $ajusteId);

        if (! in_array((string) $evento->estado, ['ACEPTADO', 'CONTINGENCIA'], true)) {
            throw new \RuntimeException('Hacienda no aceptó el Evento de Retorno (estado '.$evento->estado.'). No se aplicó la devolución.');
        }

        return $evento;
    }

    private function verificarRestante($item, int $cantidad, int $ajusteId): void
    {
        $resta = (int) $item->quantity - $this->yaDevuelto((int) $item->id, $ajusteId);
        if ($cantidad > $resta) {
            throw new \RuntimeException("Solo quedan {$resta} unidad(es) por devolver de '{$item->product?->name}'.");
        }
    }

    private function invalidar(Sale $sale, Dte $origen, array $plan, string $motivo, ?int $ventaReemplazoId, string $notas): ?Dte
    {
        $zona = config('dte.timezone', 'America/El_Salvador');
        $sello = $origen->fecha_envio ?? $origen->created_at;
        if (! PlazoInvalidacion::vigente((string) $origen->tipo_dte, $sello, null, $zona)) {
            $limite = PlazoInvalidacion::limite((string) $origen->tipo_dte, $sello, $zona)->format('d/m/Y');
            throw new \RuntimeException("Venció el plazo para invalidar este DTE ({$limite}). Corresponde otro tratamiento: consulte con el contador.");
        }

        if ($this->retornosVigentes($origen) > 0) {
            throw new \RuntimeException('La factura tiene un Evento de Retorno vigente: primero debe invalidarse ese evento.');
        }

        $notasVivas = Dte::withoutGlobalScopes()->where('dte_origen_id', $origen->id)
            ->whereIn('tipo_dte', ['05', '06'])->whereIn('estado', ['ACEPTADO', 'CONTINGENCIA', 'PENDIENTE'])->count();
        if ($notasVivas > 0) {
            throw new \RuntimeException('El CCF tiene notas de crédito/débito vigentes: primero deben invalidarse.');
        }

        $reemplazo = null;
        if ($plan['requiere_reemplazo']) {
            if (! $ventaReemplazoId || $ventaReemplazoId === (int) $sale->id) {
                throw new \RuntimeException('Indique la venta que reemplaza a esta: Hacienda exige emitir primero el documento corregido (con sello) y luego invalidar el errado.');
            }
            $reemplazo = Dte::withoutGlobalScopes()->where('sale_id', $ventaReemplazoId)->where('company_id', $sale->company_id)
                ->whereNull('dte_origen_id')->whereIn('tipo_dte', ['01', '03'])->where('estado', 'ACEPTADO')->latest('id')->first();
            if (! $reemplazo) {
                throw new \RuntimeException("La venta #{$ventaReemplazoId} no tiene un DTE aceptado por Hacienda; emítalo antes de invalidar.");
            }
        }

        $motivoTexto = trim(RutaDevolucion::etiqueta($motivo)." - venta #{$sale->id}".($notas !== '' ? ": {$notas}" : ''));
        $this->emision->invalidate($origen, (string) $plan['tipo_invalidacion'], $motivoTexto, $reemplazo?->codigo_generacion);

        return $origen->fresh();
    }
}
