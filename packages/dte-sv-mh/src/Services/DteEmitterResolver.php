<?php

namespace Stockia\DteSvMh\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stockia\DteSvMh\Models\DteEstablecimiento;
use Stockia\DteSvMh\Models\DtePuntoVenta;

/**
 * Decide desde qué establecimiento y punto de venta se emite una venta:
 * 1) el establecimiento vinculado a la bodega de la venta, si lo hay;
 * 2) si no, el establecimiento activo predeterminado de la empresa
 *    (Casa Matriz primero, luego el de menor código).
 * El punto de venta es el primero activo del establecimiento.
 */
class DteEmitterResolver
{
    /**
     * @return array{establecimiento: DteEstablecimiento, punto_venta: DtePuntoVenta}
     */
    public function forSale(Sale $sale): array
    {
        return $this->forContext((int) $sale->company_id, $sale->warehouse_id ? (int) $sale->warehouse_id : null);
    }

    /** Igual que forSale, para documentos que no nacen de una venta (p. ej. la compra de una Factura de Sujeto Excluido). */
    public function forContext(int $companyId, ?int $warehouseId): array
    {
        $establecimiento = $this->establecimientoDeBodega($warehouseId) ?? $this->predeterminado($companyId);

        if (! $establecimiento) {
            throw new \RuntimeException(
                'La empresa no tiene un establecimiento fiscal activo. Configúrelo en DTE > Establecimientos.'
            );
        }

        $puntoVenta = $establecimiento->puntosVenta()->where('activo', true)->orderBy('codigo')->first();

        if (! $puntoVenta) {
            throw new \RuntimeException(
                "El establecimiento {$establecimiento->codigo} no tiene un punto de venta activo."
            );
        }

        return ['establecimiento' => $establecimiento, 'punto_venta' => $puntoVenta];
    }

    private function establecimientoDeBodega(?int $warehouseId): ?DteEstablecimiento
    {
        if (! $warehouseId || ! Schema::hasColumn('warehouses', 'dte_establecimiento_id')) {
            return null;
        }

        $id = DB::table('warehouses')->where('id', $warehouseId)->value('dte_establecimiento_id');

        return $id
            ? DteEstablecimiento::withoutGlobalScopes()->where('id', $id)->where('activo', true)->first()
            : null;
    }

    private function predeterminado(int $companyId): ?DteEstablecimiento
    {
        return DteEstablecimiento::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('activo', true)
            ->orderByRaw("case when tipo_establecimiento = '02' then 0 else 1 end")
            ->orderBy('codigo')
            ->first();
    }
}
