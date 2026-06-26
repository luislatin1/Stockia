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
                'description' => 'Control de stock, movimientos y ajustes por almacén.',
                'version'     => '1.0.0',
                'provider'    => \App\Modules\Inventory\Providers\InventoryServiceProvider::class,
                'enabled'     => true,
                'installed_at' => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'sales',
                'name'        => 'Ventas',
                'description' => 'Gestiona ventas, cobros en efectivo, tickets y facturas locales.',
                'version'     => '1.0.0',
                'provider'    => \App\Modules\Sales\Providers\SalesServiceProvider::class,
                'enabled'     => true,
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
