<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModulesSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $now = now();

        $modules = [
            [
                'key'         => 'inventory',
                'name'        => 'Inventario',
                'description' => 'Inventario avanzado: lotes y vencimientos, ubicación de productos en estantes por bodega. El control de stock básico y los traslados entre bodegas son núcleo del sistema.',
                'version'     => '3.0.0',
                'provider'    => \App\Modules\Inventory\Providers\InventoryServiceProvider::class,
                'enabled'     => true,
                'installed_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'sales',
                'name'        => 'Ventas',
                'description' => 'Comisiones a vendedores y garantías de productos vendidos. El CRUD de ventas es núcleo del sistema y no depende de este módulo.',
                'version'     => '2.0.0',
                'provider'    => \App\Modules\Sales\Providers\SalesServiceProvider::class,
                'enabled'     => true,
                'installed_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'accounting',
                'name'        => 'Contabilidad',
                'description' => 'Bancos: catálogo de bancos, cuentas y libro de movimientos (depósitos, retiros, cheques).',
                'version'     => '0.1.0',
                'provider'    => \App\Modules\Accounting\Providers\AccountingServiceProvider::class,
                'enabled'     => false,
                'installed_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'ptv-pos',
                'name'        => 'PTV-POS',
                'description' => 'Punto de venta local con control de caja y turnos.',
                'version'     => '0.1.0',
                'provider'    => \Stockia\PTVPos\Providers\PTVPosServiceProvider::class,
                'enabled'     => true,
                'installed_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'sales-quotes',
                'name'        => 'Sales Quotes',
                'description' => 'Cotizaciones comerciales con estados y control por almacén.',
                'version'     => '0.1.0',
                'provider'    => \Stockia\SalesQuotes\Providers\SalesQuotesServiceProvider::class,
                'enabled'     => true,
                'installed_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'dte-sv-mh',
                'name'        => 'DTE SV MH',
                'description' => 'Emisión de DTE para El Salvador con integración MH y modo simulado.',
                'version'     => '0.1.0',
                'provider'    => \TuEmpresa\SvDte\DteServiceProvider::class,
                'enabled'     => true,
                'installed_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->upsert(
                [$module],
                ['key'],
                ['name', 'description', 'version', 'provider', 'enabled', 'installed_at', 'updated_at']
            );
        }
    }
}
