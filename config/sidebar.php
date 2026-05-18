<?php

return [
    'sections' => [
        [
            'label' => 'Gestion',
            'items' => [
                [
                    'icon' => '📊',
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'active' => ['dashboard'],
                ],
            ],
        ],
        [
            'label' => 'Comercial',
            'items' => [
                [
                    'icon' => '🗃️',
                    'label' => 'Ventas',
                    'route' => 'sales.index',
                    'active' => ['sales.*'],
                    'if_route' => 'sales.index',
                    'roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
                ],
                [
                    'icon' => '📝',
                    'label' => 'Cotizaciones',
                    'route' => 'salesquotes.index',
                    'active' => ['salesquotes.*'],
                    'if_route' => 'salesquotes.index',
                    'roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
                ],
                [
                    'icon' => '💹',
                    'label' => 'Resumen POS',
                    'route' => 'ptvpos.index',
                    'active' => ['ptvpos.index'],
                    'requires_route' => 'ptvpos.index',
                    'roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
                ],
                [
                    'icon' => '🛒',
                    'label' => 'Punto de Venta',
                    'route' => 'ptvpos.pos',
                    'active' => ['ptvpos.pos'],
                    'requires_route' => 'ptvpos.index',
                    'roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
                ],
                [
                    'icon' => '🔓',
                    'label' => 'Abrir Caja',
                    'route' => 'ptvpos.open',
                    'active' => ['ptvpos.open'],
                    'requires_route' => 'ptvpos.index',
                    'roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
                ],
                [
                    'icon' => '💸',
                    'label' => 'Movimientos Caja',
                    'route' => 'ptvpos.cash-movements.index',
                    'active' => ['ptvpos.cash-movements.*'],
                    'requires_route' => 'ptvpos.index',
                    'roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
                ],
                [
                    'icon' => '🔒',
                    'label' => 'Cerrar Caja',
                    'route' => 'ptvpos.close',
                    'active' => ['ptvpos.close'],
                    'requires_route' => 'ptvpos.index',
                    'roles' => ['Vendedor', 'Admin', 'SuperAdmin'],
                ],
                [
                    'icon' => '🧰',
                    'label' => 'Cajas (Admin)',
                    'route' => 'ptvpos.admin.registers.index',
                    'active' => ['ptvpos.admin.registers.*'],
                    'if_route' => 'ptvpos.admin.registers.index',
                    'requires_route' => 'ptvpos.index',
                    'roles' => ['Admin', 'SuperAdmin'],
                ],
                [
                    'icon' => '📋',
                    'label' => 'Plantillas POS',
                    'route' => 'ptvpos.admin.templates.index',
                    'active' => ['ptvpos.admin.templates.*'],
                    'if_route' => 'ptvpos.admin.templates.index',
                    'requires_route' => 'ptvpos.index',
                    'roles' => ['Admin', 'SuperAdmin'],
                ],
            ],
        ],
        [
            'label' => 'Inventario',
            'items' => [
                [
                    'icon' => '📦',
                    'label' => 'Productos',
                    'route' => 'products.index',
                    'active' => ['products.*'],
                ],
                [
                    'icon' => '🏷',
                    'label' => 'Categorias',
                    'route' => 'categories.index',
                    'active' => ['categories.*'],
                ],
                [
                    'icon' => '🔄',
                    'label' => 'Movimientos',
                    'route' => 'inventory_movements.index',
                    'active' => ['inventory_movements.*'],
                    'if_route' => 'inventory_movements.index',
                ],
                [
                    'icon' => '🛠',
                    'label' => 'Administracion Central',
                    'route' => 'core.admin.index',
                    'active' => ['core.admin.*'],
                    'if_route' => 'core.admin.index',
                ],
            ],
        ],
        [
            'label' => 'Administracion DTE',
            'items' => [
                [
                    'icon' => '🧩',
                    'label' => 'Panel DTE',
                    'route' => 'dte.admin.index',
                    'active' => ['dte.admin.*'],
                    'if_route' => 'dte.admin.index',
                    'roles' => ['Admin', 'SuperAdmin'],
                    'requires_dte_module' => true,
                ],
                [
                    'icon' => '🧾',
                    'label' => 'Clientes DTE',
                    'route' => 'dte.customers.index',
                    'active' => ['dte.customers.*'],
                    'if_route' => 'dte.customers.index',
                    'roles' => ['Admin', 'SuperAdmin'],
                    'requires_dte_module' => true,
                ],
            ],
        ],
        [
            'label' => 'Administracion',
            'items' => [
                [
                    'icon' => '👤',
                    'label' => 'Usuarios',
                    'route' => 'users.index',
                    'active' => ['users.*'],
                ],
                [
                    'icon' => '💱',
                    'label' => 'Monedas',
                    'route' => 'currencies.index',
                    'active' => ['currencies.*'],
                ],
            ],
        ],
        [
            'label' => 'Configuracion',
            'items' => [
                [
                    'icon' => '⚙️',
                    'label' => 'Panel de Control',
                    'route' => 'settings.modules.index',
                    'active' => ['settings.modules.*'],
                    'roles' => ['SuperAdmin'],
                ],
            ],
        ],
        [
            'label' => 'Sistema',
            'items' => [
                [
                    'icon' => '🔁',
                    'label' => 'Cambiar Almacen',
                    'route' => 'warehouse.select',
                    'active' => ['warehouse.select'],
                ],
                [
                    'icon' => '🚪',
                    'label' => 'Cerrar Sesion',
                    'type' => 'logout',
                ],
            ],
        ],
    ],
];
