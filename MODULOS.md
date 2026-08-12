# Módulos de Stockia

Este documento describe qué viene de fábrica (Core, siempre activo) y qué agrega cada módulo opcional activable desde **Ajustes > Módulos**. Es el resultado de la evaluación de arquitectura de módulos hecha sobre el sistema legacy DigitalPos y la reestructuración del sistema de toggles de Stockia (2026-08-12).

## 1. Cómo funciona el sistema de módulos

- **Core** = funcionalidad que vive directamente en `app/` (controladores, modelos, rutas en `routes/web.php`), **sin ningún interruptor**. Siempre está disponible, no se puede desactivar, y no depende de la tabla `modules`.
- **Módulo opcional** = funcionalidad que vive en `app/Modules/<Nombre>/` o en `packages/<paquete>/`, gateada por una fila en la tabla `modules` (`enabled = true/false`). Se activa/desactiva desde **Ajustes > Módulos** (solo rol SuperAdmin).
- **Si un módulo está desactivado:**
  - Las opciones de menú correspondientes se ven en gris con 🔒 y un tooltip explicando qué módulo activar (en vez de desaparecer sin explicación).
  - Si alguien navega directo a la URL de una función de un módulo apagado, ve una página de aviso explicando qué activar — no un error 404 genérico.
  - Nada de esto afecta al Core: las funciones básicas del negocio (vender, comprar, controlar inventario) **nunca** dependen de que un módulo esté activo.

Cada módulo tiene un texto explicativo de qué hace visible en la propia pantalla de Ajustes > Módulos (`Module::CATALOG` en `app/Models/Module.php`).

## 2. Qué es Core (siempre disponible, de fábrica)

| Área | Qué incluye |
|---|---|
| **Productos y Categorías** | CRUD completo, código de barras, SKU, ajustes de stock |
| **Proveedores** | CRUD completo |
| **Ventas** | Venta de contado, tickets, facturas, anulación/devolución administrativa, exportar a Excel |
| **Créditos y Cobros a Clientes** | Venta a crédito (saldo, días de crédito, fecha de vencimiento), registro de abonos, pantalla "Cuentas por Cobrar" |
| **Compras** | Registro de compras a proveedor con actualización automática de stock y costo |
| **Cuentas por Pagar a Proveedores** | Compras a crédito (saldo, vencimiento), registro de abonos, pantalla "Cuentas por Pagar" |
| **Inventario básico** | Stock por bodega, historial de movimientos, administración de bodegas |
| **Traslados entre Bodegas** | Envío/recepción/anulación de mercadería entre bodegas de la misma empresa, con control de stock en tránsito |
| **Usuarios y roles** | CRUD de usuarios, roles (Vendedor/Admin/SuperAdmin) por empresa |
| **Monedas** | Catálogo de monedas |
| **Administración Central** | Datos fiscales de la empresa, bodegas |

Ninguna de estas funciones se puede "desinstalar" — son la base mínima para que Stockia funcione como sistema de punto de venta e inventario.

## 3. Módulos opcionales

| Módulo (clave) | Por defecto | Qué agrega |
|---|---|---|
| **Ventas** (`sales`) | ✅ Activo | **Comisiones a vendedores** (% configurable por usuario, se genera automáticamente al completar una venta) y **Garantías de productos** (meses de garantía por producto, se genera automáticamente al vender, con flujo de reclamo/resolución). |
| **Inventario** (`inventory`) | ✅ Activo | **Lotes y Vencimientos** (registro de lotes por producto/bodega con alerta vigente/por vencer/vencido) y **Ubicación en Estantes** (asignar productos a un estante dentro de cada bodega). |
| **Contabilidad** (`accounting`) | ⬜ Inactivo | **Bancos**: catálogo de bancos, cuentas bancarias de la empresa (con moneda), y libro de movimientos (depósitos, retiros, cheques) con saldo automático. |
| **PTV-POS** (`ptv-pos`) | ✅ Activo | Punto de venta físico con control de caja, apertura/cierre de turno, movimientos de caja chica. |
| **Sales Quotes** (`sales-quotes`) | ✅ Activo | Cotizaciones comerciales con estados y control por almacén. |
| **DTE SV MH** (`dte-sv-mh`) | ✅ Activo | Facturación Electrónica (DTE) para El Salvador, integración con Ministerio de Hacienda (modo real y simulado). |

**Por qué Contabilidad viene apagada y el resto encendido:** los primeros cinco son funciones que casi cualquier negocio necesita desde el primer día (vender con comisión a empleados, garantías si vende bienes durables, un punto de venta físico, cotizar, facturar electrónicamente). Bancos es una función de tesorería más específica — muchos comercios pequeños no la necesitan y preferirían no verla si no la van a usar.

## 4. Identificado en la evaluación, aún no construido

Estas dos funciones se identificaron como candidatas naturales pero no se han construido todavía:

- **Pedidos/Apartados de clientes** — reserva de mercadería con entrega futura, distinto de una cotización. Encajaría como extensión del módulo **Sales Quotes**.
- **Libros fiscales IVA** (ventas a consumidor/contribuyente, compras) — reportes contables exigidos por la normativa salvadoreña. Encajaría como extensión del módulo **DTE SV MH**, ya que comparten la misma obligación fiscal.

## 5. Referencia técnica rápida

- **Catálogo de módulos:** `app/Models/Module::CATALOG` — nombre, descripción, versión, dependencias y clase del Service Provider de cada módulo.
- **Estado por defecto en una instalación nueva:** `database/seeders/ModulesSeeder.php`.
- **Verificar si un módulo está activo en código:** `Module::isEnabled('clave')`.
- **Menú:** `config/sidebar.php` — un ítem con `'requires_module' => 'clave'` se bloquea automáticamente (🔒) cuando ese módulo está apagado; sin esa clave, el ítem es Core y siempre se muestra (sujeto a rol).
- **Aviso al navegar directo a una URL de módulo apagado:** `app/Providers/ModuleGateServiceProvider.php` (mapa `GATED_PREFIXES` — agregar ahí el prefijo de cualquier módulo nuevo).
- **Dónde vive el código de cada módulo:**
  - `sales` → `app/Modules/Sales/` (rutas de Comisiones/Garantías; el CRUD de Ventas es Core en `routes/web.php`)
  - `inventory` → `app/Modules/Inventory/` (rutas de Lotes/Estantes; el stock/bodegas/traslados es Core en `routes/web.php`)
  - `accounting` → `app/Modules/Accounting/`
  - `ptv-pos`, `sales-quotes` → `packages/` (paquetes Composer independientes)
  - `dte-sv-mh` → paquete Composer `tuempresa/sv-dte`
