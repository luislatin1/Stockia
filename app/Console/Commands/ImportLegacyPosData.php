<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Company;
use App\Models\LegacySale;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyPosData extends Command
{
    protected $signature = 'legacy:import-pos {--company=} {--skip-history}';

    protected $description = 'Importa categorias, proveedores, productos e historico de ventas desde la base legacy `pos` (conexion "legacy").';

    private int $companyId;

    private int $warehouseId;

    public function handle(): int
    {
        $company = $this->option('company')
            ? Company::find((int) $this->option('company'))
            : (Company::count() === 1 ? Company::first() : null);

        if (! $company) {
            $this->error('No se pudo determinar la empresa destino. Usa --company=ID (hay '.Company::count().' empresas registradas).');

            return self::FAILURE;
        }

        $warehouse = Warehouse::where('company_id', $company->id)->first();

        if (! $warehouse) {
            $this->error("La empresa {$company->id} no tiene ninguna bodega. Crea una bodega antes de importar.");

            return self::FAILURE;
        }

        $this->companyId = $company->id;
        $this->warehouseId = $warehouse->id;

        $this->info("Empresa destino: {$company->name} (#{$company->id}) — bodega: {$warehouse->name} (#{$warehouse->id})");

        $categoryMap = $this->importCategories();
        $providerMap = $this->importProviders();
        $this->importProducts($categoryMap, $providerMap);

        if (! $this->option('skip-history')) {
            $this->importHistory();
        } else {
            $this->info('Historico de ventas omitido (--skip-history).');
        }

        return self::SUCCESS;
    }

    /** @return array<int, int> legacy id_categoria => categories.id */
    private function importCategories(): array
    {
        $rows = DB::connection('legacy')->table('categoria')->get();

        foreach ($rows as $row) {
            Category::updateOrCreate(
                ['company_id' => $this->companyId, 'legacy_id' => $row->id_categoria],
                [
                    'nombre' => $this->nullIfBlank($row->nombre_cat) ?? "Categoría #{$row->id_categoria}",
                    'descripcion' => $this->nullIfBlank($row->descripcion),
                ]
            );
        }

        $this->info("Categorias importadas: {$rows->count()}");

        return Category::where('company_id', $this->companyId)->pluck('id', 'legacy_id')->all();
    }

    /** @return array<int, int> legacy id_proveedor => providers.id */
    private function importProviders(): array
    {
        $rows = DB::connection('legacy')->table('proveedor')->get();

        foreach ($rows as $row) {
            Provider::updateOrCreate(
                ['company_id' => $this->companyId, 'legacy_id' => $row->id_proveedor],
                [
                    'nombre' => $this->nullIfBlank($row->nombre) ?? "Proveedor #{$row->id_proveedor}",
                    'nit' => $this->nullIfBlank($row->nit),
                    'nrc' => $this->nullIfBlank($row->nrc),
                    'dui' => $this->nullIfBlank($row->dui),
                    'telefono' => $this->nullIfBlank($row->telefono1),
                    'email' => $this->nullIfBlank($row->email),
                    'direccion' => $this->nullIfBlank($row->direccion),
                ]
            );
        }

        $this->info("Proveedores importados: {$rows->count()}");

        return Provider::where('company_id', $this->companyId)->pluck('id', 'legacy_id')->all();
    }

    private function importProducts(array $categoryMap, array $providerMap): void
    {
        $presentations = DB::connection('legacy')->table('presentacion_producto as pp')
            ->join('producto as p', 'p.id_producto', '=', 'pp.id_producto')
            ->join('presentacion as pr', 'pr.id_presentacion', '=', 'pp.id_presentacion')
            ->select([
                'pp.id_pp',
                'pp.id_producto',
                'pp.unidad',
                'pp.costo as pp_costo',
                'pp.precio',
                'pp.barcode as pp_barcode',
                'pp.activo',
                'pr.nombre as presentacion_nombre',
                'p.barcode as producto_barcode',
                'p.codart',
                'p.descripcion as producto_descripcion',
                'p.composicion',
                'p.exento',
                'p.id_categoria',
                'p.id_proveedor',
                'p.costo as producto_costo',
            ])
            ->orderBy('pp.id_producto')
            ->orderByDesc('pp.unidad')
            ->get();

        $stockMap = DB::connection('legacy')->table('stock')->pluck('stock', 'id_producto');

        $usedSkus = [];
        $usedBarcodes = [];
        $imported = 0;
        $skippedBarcodes = 0;

        foreach ($presentations->groupBy('id_producto') as $idProducto => $group) {
            $active = $group->where('activo', 1);
            $rows = $active->isEmpty() ? $group : $active;
            $multi = $rows->count() > 1;

            foreach ($rows->values() as $index => $row) {
                $isPrimary = $index === 0;

                $name = $multi
                    ? "{$row->producto_descripcion} - {$row->presentacion_nombre}"
                    : $row->producto_descripcion;

                $sku = null;
                if ($isPrimary) {
                    $candidate = trim((string) $row->codart);
                    if ($candidate !== '' && $candidate !== '0' && ! isset($usedSkus[$candidate])) {
                        $sku = $candidate;
                        $usedSkus[$candidate] = true;
                    }
                }

                $barcode = $this->resolveBarcode($row->pp_barcode, $row->producto_barcode, $usedBarcodes, $skippedBarcodes);

                $categoryId = $row->id_categoria ? ($categoryMap[$row->id_categoria] ?? null) : null;
                $providerId = $row->id_proveedor ? ($providerMap[$row->id_proveedor] ?? null) : null;

                $product = Product::updateOrCreate(
                    ['company_id' => $this->companyId, 'legacy_id' => $row->id_pp],
                    [
                        'name' => $name,
                        'sku' => $sku,
                        'barcode' => $barcode,
                        'codigo' => $sku ?? $barcode,
                        'tipo_item' => 1,
                        'uni_medida' => 59,
                        'afecto_iva' => ! ((int) $row->exento),
                        'price' => (float) $row->precio,
                        'description' => $this->nullIfBlank($row->composicion),
                        'category_id' => $categoryId,
                        'provider_id' => $providerId,
                    ]
                );

                $stock = $isPrimary ? (int) round((float) ($stockMap[$idProducto] ?? 0)) : 0;
                $cost = (float) ($row->pp_costo ?: $row->producto_costo);

                $this->upsertProductWarehouse($product->id, $stock, $cost);

                $imported++;
            }
        }

        DB::table('products')
            ->where('company_id', $this->companyId)
            ->whereNull('codigo')
            ->update(['codigo' => DB::raw("CONCAT('PRD-', id)")]);

        $this->info("Productos importados: {$imported} (barcodes duplicados omitidos: {$skippedBarcodes})");
    }

    private function resolveBarcode(?string $presentationBarcode, ?string $productBarcode, array &$used, int &$skipped): ?string
    {
        $candidate = trim((string) $presentationBarcode) ?: trim((string) $productBarcode);

        if ($candidate === '' || preg_match('/^0(\.0+)?$/', $candidate)) {
            return null;
        }

        if (isset($used[$candidate])) {
            $skipped++;

            return null;
        }

        $used[$candidate] = true;

        return $candidate;
    }

    private function upsertProductWarehouse(int $productId, int $stock, float $cost): void
    {
        $exists = DB::table('product_warehouse')
            ->where('company_id', $this->companyId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $this->warehouseId)
            ->exists();

        if ($exists) {
            DB::table('product_warehouse')
                ->where('company_id', $this->companyId)
                ->where('product_id', $productId)
                ->where('warehouse_id', $this->warehouseId)
                ->update(['stock' => $stock, 'cost' => $cost, 'updated_at' => now()]);

            return;
        }

        DB::table('product_warehouse')->insert([
            'company_id' => $this->companyId,
            'product_id' => $productId,
            'warehouse_id' => $this->warehouseId,
            'stock' => $stock,
            'min_stock' => 0,
            'cost' => $cost,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function importHistory(): void
    {
        $this->info('Importando facturas...');
        $saleCount = 0;

        DB::connection('legacy')->table('factura')
            ->chunkById(1000, function ($rows) use (&$saleCount) {
                $payload = $rows->map(fn ($r) => [
                    'company_id' => $this->companyId,
                    'legacy_id' => $r->id_factura,
                    'fecha' => $r->fecha,
                    'hora' => $r->hora ?: null,
                    'tipo' => $this->nullIfBlank($r->tipo),
                    'tipo_documento' => $this->nullIfBlank($r->tipo_documento),
                    'numero_doc' => $this->nullIfBlank($r->numero_doc),
                    'cliente_nombre' => $this->nullIfBlank($r->nombre),
                    'subtotal' => (float) $r->subtotal,
                    'iva' => (float) $r->iva,
                    'descuento' => (float) $r->descuento,
                    'total' => (float) $r->total,
                    'anulada' => (bool) $r->anulada,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                DB::table('legacy_sales')->upsert(
                    $payload,
                    ['company_id', 'legacy_id'],
                    ['fecha', 'hora', 'tipo', 'tipo_documento', 'numero_doc', 'cliente_nombre', 'subtotal', 'iva', 'descuento', 'total', 'anulada', 'updated_at']
                );

                $saleCount += count($payload);
            }, 'id_factura', 'id_factura');

        $this->info("Facturas importadas: {$saleCount}");

        $saleMap = LegacySale::where('company_id', $this->companyId)->pluck('id', 'legacy_id')->all();
        $productMap = Product::where('company_id', $this->companyId)->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();

        $this->info('Importando lineas de factura...');
        $itemCount = 0;
        $skipped = 0;

        DB::connection('legacy')->table('factura_detalle')
            ->chunkById(2000, function ($rows) use (&$itemCount, &$skipped, $saleMap, $productMap) {
                $payload = [];

                foreach ($rows as $r) {
                    $saleId = $saleMap[$r->id_factura] ?? null;

                    if (! $saleId) {
                        $skipped++;

                        continue;
                    }

                    $payload[] = [
                        'legacy_sale_id' => $saleId,
                        'product_id' => $productMap[$r->id_presentacion] ?? null,
                        'legacy_id' => $r->id_factura_detalle,
                        'descripcion' => null,
                        'cantidad' => (float) $r->cantidad,
                        'precio_venta' => (float) $r->precio_venta,
                        'subtotal' => (float) $r->subtotal,
                        'exento' => (bool) $r->exento,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($payload) {
                    DB::table('legacy_sale_items')->upsert(
                        $payload,
                        ['legacy_sale_id', 'legacy_id'],
                        ['product_id', 'descripcion', 'cantidad', 'precio_venta', 'subtotal', 'exento', 'updated_at']
                    );

                    $itemCount += count($payload);
                }
            }, 'id_factura_detalle', 'id_factura_detalle');

        $this->info("Lineas de factura importadas: {$itemCount} (omitidas por venta no encontrada: {$skipped})");
    }

    private function nullIfBlank(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
