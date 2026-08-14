<?php

namespace App\Models;

class Permission
{
    public const CATALOG = [
        // Comercial
        'ventas' => [
            'label' => 'Ventas',
            'section' => 'Comercial',
            'default_roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
        ],
        'cuentas-cobrar' => [
            'label' => 'Cuentas por Cobrar',
            'section' => 'Comercial',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'comisiones' => [
            'label' => 'Comisiones',
            'section' => 'Comercial',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'garantias' => [
            'label' => 'Garantías',
            'section' => 'Comercial',
            'default_roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
        ],
        'cotizaciones' => [
            'label' => 'Cotizaciones',
            'section' => 'Comercial',
            'default_roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
        ],
        'apartados' => [
            'label' => 'Apartados',
            'section' => 'Comercial',
            'default_roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
        ],
        'ptvpos' => [
            'label' => 'Punto de Venta (POS)',
            'section' => 'Comercial',
            'default_roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
        ],
        'ptvpos-admin' => [
            'label' => 'Administración de Cajas POS',
            'section' => 'Comercial',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],

        // Compras
        'compras' => [
            'label' => 'Compras',
            'section' => 'Compras',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'cuentas-pagar' => [
            'label' => 'Cuentas por Pagar',
            'section' => 'Compras',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'proveedores' => [
            'label' => 'Proveedores',
            'section' => 'Compras',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],

        // Inventario
        'productos' => [
            'label' => 'Productos',
            'section' => 'Inventario',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'categorias' => [
            'label' => 'Categorías',
            'section' => 'Inventario',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'inventory-movements' => [
            'label' => 'Movimientos de Inventario',
            'section' => 'Inventario',
            'default_roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
        ],
        'stock-transfers' => [
            'label' => 'Traslados entre Bodegas',
            'section' => 'Inventario',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'inventory-lotes' => [
            'label' => 'Lotes y Vencimientos',
            'section' => 'Inventario',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'inventory-estantes' => [
            'label' => 'Ubicación en Estantes',
            'section' => 'Inventario',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'core-admin' => [
            'label' => 'Administración Central',
            'section' => 'Inventario',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],

        // Contabilidad
        'accounting' => [
            'label' => 'Bancos',
            'section' => 'Contabilidad',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],

        // Administracion DTE
        'dte-admin' => [
            'label' => 'Panel DTE',
            'section' => 'Administracion DTE',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'dte-customers' => [
            'label' => 'Clientes DTE',
            'section' => 'Administracion DTE',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'dte-books' => [
            'label' => 'Libros IVA',
            'section' => 'Administracion DTE',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],

        // Administracion
        'users' => [
            'label' => 'Usuarios',
            'section' => 'Administracion',
            'default_roles' => ['Admin', 'SuperAdmin'],
            'overridable' => false,
        ],
        'monedas' => [
            'label' => 'Monedas',
            'section' => 'Administracion',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],

        // Configuracion
        'settings-modules' => [
            'label' => 'Panel de Control (Módulos)',
            'section' => 'Configuracion',
            'default_roles' => ['SuperAdmin'],
            'overridable' => false,
        ],

        // Pantallas sin item de menu (placeholders sin construir todavia)
        'inventory-stub' => [
            'label' => 'Inventario (placeholder sin construir)',
            'section' => 'Configuracion',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
        'sale-items-stub' => [
            'label' => 'Venta de Artículos (placeholder roto)',
            'section' => 'Configuracion',
            'default_roles' => ['Admin', 'SuperAdmin'],
        ],
    ];

    public static function isOverridable(string $key): bool
    {
        return self::CATALOG[$key]['overridable'] ?? true;
    }

    public static function defaultRoles(string $key): array
    {
        return self::CATALOG[$key]['default_roles'] ?? [];
    }

    public static function userCan(?CompanyUser $companyUser, string $key): bool
    {
        if (! $companyUser) {
            return false;
        }

        if (! isset(self::CATALOG[$key])) {
            return false;
        }

        if (self::isOverridable($key)) {
            $override = CompanyUserPermission::where('company_user_id', $companyUser->id)
                ->where('permission_key', $key)
                ->first();

            if ($override) {
                return $override->allowed;
            }
        }

        return in_array($companyUser->role, self::defaultRoles($key), true);
    }
}
