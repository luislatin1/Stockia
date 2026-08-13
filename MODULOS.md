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

## 4. Identificado en la evaluación — estado

- **Pedidos/Apartados de clientes** ✅ construido (2026-08-13) — reserva mercadería con entrega futura y descuenta stock de inmediato (se revierte si se anula). Vive dentro del paquete `packages/sales-quotes` (`CustomerOrderController`, rutas `customerorders.*`, tablas `customer_orders`/`customer_order_items`).
- **Libros fiscales IVA** ✅ construido (2026-08-13) — Ventas a Contribuyente, Ventas a Consumidor (resumen diario) y Compras, con export CSV. Vive en `app/Http/Controllers/DteVatBooksController.php`, rutas `dte.books.*`. Construirlo destapó que Compras nunca capturaba IVA real (siempre quedaba en 0) — se corrigió derivando gravado/exento del campo `afecto_iva` de cada producto, sin agregar campos al formulario.

## 5. Referencia técnica rápida

- **Catálogo de módulos:** `app/Models/Module::CATALOG` — nombre, descripción, versión, dependencias y clase del Service Provider de cada módulo.
- **Estado por defecto en una instalación nueva:** `database/seeders/ModulesSeeder.php`.
- **Verificar si un módulo está activo en código:** `Module::isEnabled('clave')` (cachea por request desde 2026-08-13, ver sección de rendimiento en `MIGRATION_STATUS.md`).
- **Menú:** `config/sidebar.php` — un ítem con `'requires_module' => 'clave'` se bloquea automáticamente (🔒) cuando ese módulo está apagado; sin esa clave, el ítem es Core y siempre se muestra (sujeto a rol).
- **Aviso al navegar directo a una URL de módulo apagado:** `app/Providers/ModuleGateServiceProvider.php` (mapa `GATED_PREFIXES` — agregar ahí el prefijo de cualquier módulo nuevo).
- **Dónde vive el código de cada módulo:** ver la sección 6 — solo `ptv-pos` y `sales-quotes` son paquetes Composer reales y autocontenidos (`packages/`). Los otros cuatro (`sales`, `inventory`, `accounting`, `dte-sv-mh`) son solo un interruptor: su código vive mezclado directo en `app/` junto con el Core, no en una carpeta propia.

## 6. Mapa de extracción — qué archivos pertenecen a cada módulo

Evaluación hecha el 2026-08-13 al plantearse sacar los módulos hacia un sistema nuevo. Conclusión: **solo 2 de los 6 módulos son paquetes reales y extraíbles hoy.** Los otros 4 son un toggle (`modules` + `ModuleGateServiceProvider`) sin separación física de código — extraerlos hoy significa ir archivo por archivo, no copiar una carpeta.

**Supuesto de este mapa:** el sistema nuevo mantiene las mismas convenciones base de Stockia (empresa/bodega por sesión, roles Vendedor/Admin/SuperAdmin, modelos `Product`/`InventoryMovement`/`Customer`). Si el sistema nuevo NO las tuviera, hasta `ptv-pos` y `sales-quotes` necesitarían una capa de adaptación, no solo copiar la carpeta.

### ✅ Paquetes reales y extraíbles (`packages/`)

- **`ptv-pos`** y **`sales-quotes`** — cada uno con su propio `composer.json`, `src/`, `database/migrations/`, `resources/views/`, `routes/web.php`. Se pueden copiar la carpeta completa a otro proyecto con las mismas convenciones y funcionan con cambios mínimos.
- **Punto de acoplamiento a quitar al extraer:** ambos Service Providers (`PTVPosServiceProvider`, `SalesQuotesServiceProvider`) hacen `if (Module::tableReady() && ! Module::isEnabled($key)) return;` antes de cargar rutas/vistas/migraciones — en un sistema sin capa de módulos, basta borrar ese `if` (o dejar que siempre sea `true`) para que el paquete cargue siempre.
- **Otros puntos de acoplamiento que sí se llevan sin cambios** (porque el sistema nuevo los mantiene, según lo acordado): `session('current_company_id'/'current_warehouse_id')`, el helper `currentRole()`, el middleware `role:...`, y las referencias a `App\Models\Product`/`InventoryMovement`/`Customer`.

### ❌ Solo interruptor — código mezclado en `app/`, sin paquete propio

**`sales`** (Comisiones + Garantías — el CRUD de Ventas en sí es Core, no se mueve):
- Controladores: `app/Http/Controllers/CommissionController.php`, `WarrantyController.php`
- Modelos: `app/Models/Commission.php`, `Warranty.php`
- Migraciones: `database/migrations/2026_02_18_080900_create_commissions_table.php`, `..._081100_create_warranties_table.php`
- Vistas: `resources/views/commissions/`, `resources/views/warranties/`
- Rutas: inline en `routes/web.php` (no en archivo propio); `app/Modules/Sales/Routes/web.php` solo las reexpone
- Gancho cruzado con Core: `Sale::booted()` genera la comisión, `SaleItem::booted()` genera la garantía — **si se extrae este módulo, hay que decidir qué pasa con esos dos hooks en el modelo Core `Sale`/`SaleItem`**, no son parte del paquete pero dependen de que exista.

**`inventory`** (Lotes/Vencimientos + Estantes — stock/bodegas/traslados son Core):
- Controladores: `ProductBatchController.php`, `ProductLocationController.php`
- Modelos: `ProductBatch.php`, `ProductLocation.php`
- Migraciones: `..._081700_create_product_batches_table.php`, `..._081800_create_product_locations_table.php`
- Vistas: `resources/views/product-batches/`, `resources/views/product-locations/`
- Rutas: inline en `routes/web.php`

**`accounting`** (Bancos):
- Controladores: `BankController.php`, `BankAccountController.php`, `BankMovementController.php`
- Modelos: `Bank.php`, `BankAccount.php`, `BankMovement.php`
- Migraciones: `..._081200_create_banks_table.php`, `..._081300_create_bank_accounts_table.php`, `..._081400_create_bank_movements_table.php`
- Vistas: `resources/views/accounting/{banks,bank-accounts,bank-movements}/`
- Rutas: inline en `routes/web.php`

**`dte-sv-mh`** (el más grande y el más enredado con el Core):
- Controladores: `DteAdminController.php`, `DteCustomerController.php`, `DteVatBooksController.php`
- Modelos: `Dte.php`, `DteCorrelative.php`, `DteSetting.php`, `DteInvalidation.php`
- Servicios: todo `app/Services/Dte/` (emisión, cálculo, correlativos, gateway real/simulado)
- Validadores: `app/Validators/ValidadorDocumentoSalvador.php`, `ValidadorCoherenciaDte.php`
- Middleware: `app/Http/Middleware/ValidarDocumentoDte.php` (alias `dte.document`, aplicado a `sales.store`)
- Migraciones propias: las 7 `..._0600xx-0606xx_*dte*`
- **Migración que altera tablas Core** (no es "propia" del módulo, pero el módulo no funciona sin ella): `..._060000_prepare_dte_core_schema.php` agrega columnas fiscales a `companies`, `products`, `sales`, `sale_items` — si se apaga/quita DTE, esas columnas quedarían huérfanas en el Core.
- Vistas: `resources/views/dte/{admin,books,customers}/`
- Seeder de catálogos: `database/seeders/DteCatalogSeeder.php`
- El paquete Composer `tuempresa/sv-dte` (`ExportPlugins/sv-dte-laravel12/`) casi no tiene código propio real — solo helpers/builders/validators usados opcionalmente vía `class_exists()`; no registra rutas ni migra nada. No es una base útil para extraer el módulo tal cual está hoy.

### Cómo regenerar los ZIP de distribución (`ExportPlugins/*.zip`)

No existía un script para esto — cada ZIP se regeneró manualmente el 2026-08-13 con PowerShell (`Compress-Archive`), zipeando el **contenido** de la carpeta fuente sin envolverla en una carpeta con el nombre del paquete (así es como ya estaban armados los ZIP anteriores):

```powershell
Compress-Archive -Path "packages\ptv-pos\*" -DestinationPath "ExportPlugins\ptv-pos.zip" -Force
Compress-Archive -Path "packages\sales-quotes\*" -DestinationPath "ExportPlugins\sales-quotes.zip" -Force
Compress-Archive -Path "ExportPlugins\sv-dte-laravel12\*" -DestinationPath "ExportPlugins\sv-dte-laravel12.zip" -Force
```

`sv-dte-laravel12.zip` es un caso especial: no existe un `packages/sv-dte-laravel12/` separado — el código fuente real de ese paquete vive directo en `ExportPlugins/sv-dte-laravel12/` (ver sección 6), así que el ZIP se genera de esa misma carpeta.

**Regenerar el ZIP de un módulo después de cambiarlo no es automático** — es un paso manual que hay que acordarse de correr (y comitear) cada vez que se toque `packages/ptv-pos/`, `packages/sales-quotes/` o `ExportPlugins/sv-dte-laravel12/`.

### Qué se necesitaría para que estos 4 sean paquetes reales (no se hizo, es la siguiente decisión)

Crear `packages/sales`, `packages/inventory`, `packages/accounting`, `packages/dte-sv-mh` con su propio `composer.json` + `src/` + `database/migrations/` + `resources/views/` + `routes/web.php`, mover cada archivo de la lista de arriba, y actualizar los `namespace` de controladores/modelos y las referencias en `routes/web.php`/`config/sidebar.php`. Es un refactor real (mover +30 archivos y reescribir sus namespaces), no una tarea de una sesión — por eso quedó como mapa/checklist por ahora en vez de ejecutarse.
