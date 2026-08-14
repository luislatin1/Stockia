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

## 4bis. Auditoría de completitud contra DigitalPos (2026-08-14)

Con los 6 módulos ya empaquetados, se hizo una segunda pasada leyendo el código real de `digital/pos/` (no solo nombres de archivo) para confirmar que no falte ninguna función real del sistema legacy. Resultado: **prácticamente completo**, con un solo gap identificado — documentado aquí, sin construir todavía (decisión del usuario 2026-08-14).

**Confirmado que NO son gaps** (ya cubiertos o nunca fueron una función real en el propio legacy):
- **`admin_pedidos_prov.php`** (Pedidos a Proveedores) — es un documento paralelo sin integración real: los campos que sugerirían un flujo "pedido → luego se recibe como compra" (`estado`, `numero_factura`, `cant_facturado`) nunca se usan en el código, y ni siquiera toca inventario. No hay conexión con `admin_compra.php`. No vale la pena portarlo.
- **`admin_asignacion.php`/`admin_movimiento_asignacion.php`** (Asignación/Reasignación) — es el mismo mecanismo de estantes/ubicaciones que ya vive en el módulo `inventory` (Lotes/Estantes), no una función de vendedor de ruta como se sospechó inicialmente.
- **`admin_traslados.php` vs `admin_transferencia.php`** — son dos cosas distintas (traslado entre sucursales con estado en-tránsito vs. transferencia instantánea entre ubicaciones de la misma sucursal), pero la segunda se solapa conceptualmente con lo que ya cubre `inventory`.

**Gap real encontrado — Autorización de precio (`admin_autorizacion.php`):**
En DigitalPos cada usuario/vendedor tiene un techo de nivel de precio (`usuario.precios`) y cada producto puede tener hasta 6 niveles de precio (`presentacion_producto_precio`). Un supervisor genera un código de un solo uso que, capturado en el checkout, sube temporalmente ese techo para esa venta puntual, quedando auditado en la factura (`factura.precio_aut`/`factura.clave`). **No existe en Stockia** porque durante la migración de datos (2026-08-11, ver sección 7 de `MIGRATION_STATUS.md`) se decidió explícitamente no construir niveles de precio por falta de caso de uso concreto — esta función de DigitalPos es precisamente ese caso de uso. Requeriría primero construir la infraestructura de niveles de precio por producto antes de poder construir la autorización en sí. Pendiente de decisión de negocio: no se sabe si esta función se llegó a usar en la operación real de Quezalteca.

## 5. Referencia técnica rápida

- **Catálogo de módulos:** `app/Models/Module::CATALOG` — nombre, descripción, versión, dependencias y clase del Service Provider de cada módulo.
- **Estado por defecto en una instalación nueva:** `database/seeders/ModulesSeeder.php`.
- **Verificar si un módulo está activo en código:** `Module::isEnabled('clave')` (cachea por request desde 2026-08-13, ver sección de rendimiento en `MIGRATION_STATUS.md`).
- **Menú:** `config/sidebar.php` — un ítem con `'requires_module' => 'clave'` se bloquea automáticamente (🔒) cuando ese módulo está apagado; sin esa clave, el ítem es Core y siempre se muestra (sujeto a rol).
- **Aviso al navegar directo a una URL de módulo apagado:** `app/Providers/ModuleGateServiceProvider.php` (mapa `GATED_PREFIXES` — agregar ahí el prefijo de cualquier módulo nuevo).
- **Dónde vive el código de cada módulo:** los 6 módulos son ahora paquetes Composer reales y autocontenidos bajo `packages/` (`ptv-pos`, `sales-quotes`, `accounting`, `inventory`, `sales`, `dte-sv-mh`), cada uno con su propio `composer.json`, `src/`, `database/migrations/`, `resources/views/`, `routes/web.php` y Service Provider gateado por `Module::isEnabled()`. Ya no existe el patrón intermedio `app/Modules/<Nombre>` (se retiró junto con esta extracción, ver sección 6). El único paquete que vive fuera de `packages/` es `tuempresa/sv-dte` (`ExportPlugins/sv-dte-laravel12/`) — una librería de bajo nivel externa (builders/validators de MH), no un módulo de Stockia.

## 6. Extracción a paquetes reales — completada (2026-08-13/14)

Los 4 módulos que antes eran "solo interruptor" (`sales`, `inventory`, `accounting`, `dte-sv-mh`) ya se extrajeron a `packages/` siguiendo el mismo patrón de `ptv-pos`/`sales-quotes`. Orden de extracción: `accounting` → `inventory` → `sales` → `dte-sv-mh` (de menor a mayor acoplamiento con Core). Cada fase se verificó con pruebas en transacciones revertidas (crear registros de prueba, confirmar el comportamiento, hacer rollback) antes de pasar a la siguiente.

**Supuesto de este mapa (igual que antes):** el sistema nuevo mantiene las mismas convenciones base de Stockia (empresa/bodega por sesión, roles Vendedor/Admin/SuperAdmin, modelos `Product`/`InventoryMovement`/`Customer`/`Sale`/`Company`).

### `accounting` (Bancos) — el más simple

Ya tenía rutas propias y cero hooks en modelos Core. `packages/accounting/`: controladores `Bank*Controller`, modelos `Bank`/`BankAccount`/`BankMovement` (namespace `Stockia\Accounting\`), sus 3 migraciones, sus 6 vistas. Sin acoplamiento cruzado que resolver.

### `inventory` (Lotes/Vencimientos + Estantes)

`packages/inventory/`: `ProductBatchController`/`ProductLocationController`, modelos `ProductBatch`/`ProductLocation` (namespace `Stockia\Inventory\`), sus 2 migraciones (ninguna altera tablas Core), sus 4 vistas. Único acoplamiento: `app/Models/Product.php::batches()`/`locations()` ahora referencian `Stockia\Inventory\Models\ProductBatch`/`ProductLocation` — Core sigue necesitando esas clases en tiempo de compilación, pero eso ya es igual de aceptable que el acoplamiento inverso que ya tenían `ptv-pos`/`sales-quotes`.

### `sales` (Comisiones + Garantías) — el más delicado de los 3 primeros

`packages/sales/`: `CommissionController`/`WarrantyController`, modelos `Commission`/`Warranty` (namespace `Stockia\Sales\`), sus 2 migraciones propias **más las 2 que alteran tablas Core** (`comision_porcentaje` en `company_user`, `garantia_meses` en `products` — se movieron al paquete porque son columnas genuinamente opcionales, a diferencia de las de DTE), sus 4 vistas. Los hooks `Sale::booted()`/`SaleItem::booted()` que crean `Commission`/`Warranty` automáticamente se quedan en los modelos Core `Sale`/`SaleItem` (igual que antes, gateados por `Module::isEnabled('sales')`) pero ahora importan las clases desde `Stockia\Sales\Models\`. Verificado con una venta de prueba: comisión y garantía se siguen generando correctamente.

### `dte-sv-mh` (DTE Ministerio de Hacienda) — el más grande, con un límite de diseño importante

**Hallazgo clave durante la extracción:** `DteCalculationService` (cálculo de IVA 13%, gravado/exento/retenciones) no es parte "opcional" de DTE — `SaleController::store()` lo usa para calcular el total de **toda** venta, DTE o no. Por eso el límite del paquete quedó así:

- **Se queda en Core permanentemente** (no es parte del paquete): `app/Services/Dte/DteCalculationService.php`, la migración `2026_02_18_060000_prepare_dte_core_schema.php` completa (columnas fiscales en `companies`/`products`/`sales`/`sale_items`), y las escrituras de esas columnas en `SaleController::store()`.
- **Se movió a `packages/dte-sv-mh/`** (namespace `Stockia\DteSvMh\`, lo que sí es prescindible — la integración con Hacienda): `DteEmissionService`, `DteCorrelativeService`, `DteGatewayFactory` + `Contracts`/`Real`/`Simulation` (incluyendo `SimuladorMhValidator`, que vivía suelto en `app/Services/`), modelos `Dte`/`DteCorrelative`/`DteSetting`/`DteInvalidation`, controladores `DteAdminController`/`DteCustomerController`/`DteVatBooksController`, validadores `ValidadorDocumentoSalvador`/`ValidadorCoherenciaDte`, middleware `ValidarDocumentoDte` (alias `dte.document`, actualizado en `bootstrap/app.php`), sus 6 migraciones propias (no la `prepare_dte_core_schema`), y el seeder `DteCatalogSeeder` — con sus JSON de catálogos copiados a `packages/dte-sv-mh/database/data/dte/` (antes vivían solo en `ExportPlugins/sv-dte-laravel12/`, lo que habría dejado el ZIP del paquete incompleto).
- **Arreglo de gate que faltaba:** el toggle de `dte-sv-mh` no gateaba nada en runtime — `SaleController::store()` y `packages/ptv-pos`'s `PTVPosController::store()` (¡también emite DTE en su propio checkout!) llamaban `DteEmissionService::emitForSale()` sin chequear `Module::isEnabled()`, y `ModuleGateServiceProvider::GATED_PREFIXES` no tenía entrada para `dte-sv-mh`. Ambos controladores ahora chequean `Module::isEnabled('dte-sv-mh')` antes de emitir, y se agregó `'dte-sv-mh' => ['dte']` al mapa de prefijos gateados.
- **Bug preexistente encontrado y arreglado (2026-08-14):** en modo `simulacion`/`real` (no en `contingencia`), `DteEmissionService::emitForSale()`/`resendContingency()` hacían `DB::table('dte_send_attempts')->insert(['response_payload' => $sendResult['response'], ...])` con un array PHP crudo en una columna JSON — MySQL rechazaba el insert (`Invalid JSON text`). Nunca se había detectado antes porque no había ventas reales en la BD de desarrollo. Se envolvió el valor en `json_encode()` en ambos lugares; verificado con una venta de prueba en modo `simulacion` que ahora llega a `estado: ACEPTADO`.

### Limpieza consecuente

El patrón intermedio `app/Modules/<Nombre>` (usado solo por estos 4 módulos antes de tener paquete propio) quedó completamente huérfano tras la extracción — se eliminó entero (`app/Modules/Core/Providers/ModuleServiceProvider.php` y `app/Modules/README.md`, sin más consumidores en el código).

### Regenerar los ZIP de distribución (`ExportPlugins/*.zip`)

Ahora los 6 módulos bajo `packages/` (incluyendo los 4 recién extraídos) se exportan con el comando ya existente:

```bash
php artisan modules:export <nombre>   # accounting | inventory | sales | dte-sv-mh | ptv-pos | sales-quotes
```

`tuempresa/sv-dte` sigue siendo la excepción — no vive en `packages/`, así que su ZIP se sigue regenerando a mano:

```powershell
Compress-Archive -Path "ExportPlugins\sv-dte-laravel12\*" -DestinationPath "ExportPlugins\sv-dte-laravel12.zip" -Force
```

**Regenerar el ZIP de un módulo después de cambiarlo sigue sin ser automático** — es un paso manual que hay que acordarse de correr (y comitear) cada vez que se toque cualquier `packages/<módulo>/` o `ExportPlugins/sv-dte-laravel12/`. Ver `[[feedback-module-packaging]]` en la memoria de Claude para este hábito.

## 7. Permisos granulares por pantalla (2026-08-14)

Cierra el gap real encontrado en la auditoría de completitud contra DigitalPos (sección 4bis): los 3 roles fijos (Vendedor/Admin/SuperAdmin) ahora tienen una capa de **excepciones por usuario y por pantalla** encima, sin reemplazarlos — comportamiento idéntico a hoy si nadie configura ninguna excepción.

- **Catálogo:** `App\Models\Permission::CATALOG` — ~27 claves (una por pantalla o grupo de pantallas que ya comparten el mismo gate de rol; ver el archivo para la lista completa), cada una con `label`, `section` (mismas secciones de `config/sidebar.php`), `default_roles` y `overridable` (default `true`).
- **No overridable** (sin capa de excepciones, solo rol, como barrera anti-escalamiento de privilegios): `users` (la pantalla de Usuarios) y `settings-modules` (Ajustes > Módulos).
- **Excepción por usuario:** tabla `company_user_permissions` (`company_user_id`, `permission_key`, `allowed`) — la sola presencia de una fila es la excepción; sin fila, se usa el rol de siempre. `Permission::userCan()` es el único punto de verdad.
- **Aplicación:** Gates nativos de Laravel, registrados en `AppServiceProvider::boot()` (uno por clave overridable) y aplicados con el middleware `can:<clave>` **junto al** `role:...` que ya existía en cada grupo de rutas (no lo reemplaza) — en `routes/web.php` y en los 6 `packages/*/routes/web.php`.
- **Sidebar:** `resources/views/layouts/partials/sidebar.blade.php` ya traía soporte sin usar para `$item['can']` (autorización nativa) — se aprobechó agregando `'can' => 'clave'` a los ítems de `config/sidebar.php`, sin tocar ninguna plantilla Blade.
- **Administración:** botón "Permisos" en `users.index` → `UserPermissionController` (SuperAdmin-only), un panel por sección con 3 estados por permiso (Heredado / Forzar Sí / Forzar No), guardado borra-y-reinserta las excepciones de ese usuario en una transacción.
- **De paso, arreglados 2 hallazgos independientes de la misma auditoría de rutas:** `currencies.index`, `inventory.index` y `sale_items.index` vivían **fuera** del grupo `auth` en `routes/web.php` — accesibles sin iniciar sesión; y `app/Http/Middleware/RoleMiddleware.php` era un duplicado sin uso de `EnsureCompanyRole` — eliminado.
- **Efecto colateral bueno:** varios ítems de `config/sidebar.php` (Productos, Categorías, Compras, Cuentas por Pagar, Proveedores, Administración Central, Usuarios) no tenían `roles` pese a que su ruta sí exigía Admin/SuperAdmin — un Vendedor los veía en el menú aunque no podía usarlos. Al agregar `can`/`roles` de forma consistente (basado en el gate real de cada ruta, no en lo que el sidebar ya declaraba) esto quedó corregido.
