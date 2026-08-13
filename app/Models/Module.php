<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Module extends Model
{
    public const CATALOG = [
        'sales' => [
            'name' => 'Ventas',
            'description' => 'Comisiones a vendedores y garantías de productos vendidos. El CRUD de ventas es núcleo del sistema y no depende de este módulo.',
            'version' => '2.0.0',
            'dependencies' => [],
            'provider' => \App\Modules\Sales\Providers\SalesServiceProvider::class,
        ],
        'inventory' => [
            'name' => 'Inventario',
            'description' => 'Inventario avanzado: lotes y vencimientos, ubicación de productos en estantes por bodega. El control de stock básico y los traslados entre bodegas son núcleo del sistema.',
            'version' => '3.0.0',
            'dependencies' => [],
            'provider' => \App\Modules\Inventory\Providers\InventoryServiceProvider::class,
        ],
        'accounting' => [
            'name' => 'Contabilidad',
            'description' => 'Bancos: catálogo de bancos, cuentas y libro de movimientos (depósitos, retiros, cheques).',
            'version' => '0.1.0',
            'dependencies' => [],
            'provider' => \App\Modules\Accounting\Providers\AccountingServiceProvider::class,
        ],
        'ptv-pos' => [
            'name' => 'PTV-POS',
            'description' => 'Punto de venta local con control de caja y turnos.',
            'version' => '0.1.0',
            'dependencies' => [],
            'provider' => \Stockia\PTVPos\Providers\PTVPosServiceProvider::class,
        ],
        'sales-quotes' => [
            'name' => 'Sales Quotes',
            'description' => 'Cotizaciones comerciales con estados y control por almacén.',
            'version' => '0.1.0',
            'dependencies' => [],
            'provider' => \Stockia\SalesQuotes\Providers\SalesQuotesServiceProvider::class,
        ],
        'dte-sv-mh' => [
            'name' => 'DTE SV MH',
            'description' => 'Emisión de DTE para El Salvador con integración MH y modo simulado.',
            'version' => '0.1.0',
            'dependencies' => [],
            'provider' => \TuEmpresa\SvDte\DteServiceProvider::class,
        ],
    ];

    protected $fillable = [
        'key',
        'name',
        'description',
        'version',
        'provider',
        'enabled',
        'installed_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'installed_at' => 'datetime',
    ];

    private static ?bool $tableReadyCache = null;

    private static ?array $enabledKeysCache = null;

    public static function tableReady(): bool
    {
        if (self::$tableReadyCache === null) {
            try {
                self::$tableReadyCache = Schema::hasTable('modules');
            } catch (\Throwable) {
                self::$tableReadyCache = false;
            }
        }

        return self::$tableReadyCache;
    }

    public static function isEnabled(string $key): bool
    {
        if (! static::tableReady()) {
            return false;
        }

        if (self::$enabledKeysCache === null) {
            self::$enabledKeysCache = static::where('enabled', true)->pluck('key')->all();
        }

        return in_array($key, self::$enabledKeysCache, true);
    }

    public static function label(string $key): string
    {
        return self::CATALOG[$key]['name'] ?? $key;
    }
}
