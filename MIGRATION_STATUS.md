# Estado de migración — Stockia (2026-08-11)

Documento de traspaso: entorno movido de `C:\laragonDQ\laragon\www\Stockia` (PHP 7.2, Apache VC15) a `C:\laragonOld\www\Stockia` (PHP 8.3.16). Este archivo resume todo lo hecho en la sesión de hoy para continuar el trabajo desde VS Code.

## 1. Entorno

- **Motivo del cambio:** Apache de `laragonDQ` solo tenía PHP 7.2.19 instalado (`mod_php.conf` cargaba ese módulo), pero el proyecto requiere PHP >= 8.2. `laragonOld` ya tenía PHP 8.3.16 correctamente cargado en Apache (`php8apache2_4.dll`, VC16).
- **Código:** `C:\laragonOld\www\Stockia` ya era una copia idéntica de `laragonDQ` (mismo commit git, mismos cambios sin confirmar) — no fue necesario copiar nada de código.
- **Bug encontrado y corregido:** los 3 paquetes Composer locales (`stockia/ptv-pos`, `stockia/sales-quotes`, `tuempresa/sv-dte`) se instalan como **junctions de Windows** en `vendor/`. Al copiarse la carpeta, esos junctions llegaron como carpetas vacías (rompía el arranque de Laravel). Se arregló borrando esas carpetas vacías y corriendo `composer install` (recrea los junctions correctamente).
- **Fix de tema oscuro:** [tailwind.config.js](tailwind.config.js) — se agregó `darkMode: 'class'`. Antes, las clases `dark:` de los componentes Breeze se activaban solas según el sistema operativo del usuario (estrategia `media` por defecto), causando mezcla de estilos claros/oscuros en `/register` y `/login`. Ahora solo se activan si algo agrega la clase `dark` explícitamente (nada la agrega hoy, así que la app queda siempre en tema claro, que es lo que especifica la guía de marca).

## 2. Bases de datos migradas (MySQL 5.7.24 → MySQL 8.4.3)

| Base | Filas relevantes | Notas |
|---|---|---|
| `stockia` | Esquema completo, datos de negocio en 0 (BD de dev) | Usuario `stockia` / `StockiaDB2026!` recreado igual que en origen |
| `pos` (proyecto legacy `digital/pos`) | 80 tablas, ~95MB reales — 1,614 productos, 96,134 facturas, 412,743 líneas de detalle | Usuario/clave original (`root`/`Adm$2020**.`) no coincidía con el root sin clave de `laragonOld` → se editó [`digital/pos/_conexion.php`](../digital/pos/_conexion.php) para usar clave vacía |

Dumps de respaldo en `C:\laragonDQ\laragon\_migration\` (`stockia.sql`, `pos.sql`).

## 3. Conflicto de puerto 3306 (importante, puede repetirse)

Al arrancar el MariaDB de tu WSL (`SOPORTE-ITLL`) para el soporte que hicimos, quedó reenviando su puerto 3306 al `127.0.0.1` de Windows vía `wslrelay.exe` (WSL en modo de redes "mirrored" reenvía puertos automáticamente). Esto tapa el MySQL de `laragonOld`, que solo queda alcanzable por `0.0.0.0`/`::` (no por el loopback específico).

**Workaround aplicado:** usar `127.0.0.2` en vez de `127.0.0.1`/`localhost` en:
- [`Stockia/.env`](.env) → `DB_HOST=127.0.0.2`
- [`digital/pos/_conexion.php`](../digital/pos/_conexion.php) → `$hostname = "127.0.0.2"`

Si cierras esa WSL (o el problema no se repite), se puede revertir a `127.0.0.1`. Mientras la WSL esté corriendo con ese port-forwarding activo, cualquier herramienta nueva que conecte a MySQL desde Windows debe usar `127.0.0.2` (o el IP LAN real), no `localhost`/`127.0.0.1`.

## 4. WSL `SOPORTE-ITLL` — fix de MariaDB (fuera del scope de Stockia, pero mismo entorno)

`mariadb.service` fallaba al arrancar por `/run/mariadb` inexistente (`systemd-tmpfiles-setup.service` está **masked** en esa WSL, así que nunca recrea ese directorio en cada boot). Fix aplicado vía `systemctl edit mariadb.service`:
```ini
[Service]
RuntimeDirectory=mariadb
RuntimeDirectoryMode=0755
```
Esto sobrevive a reinicios de la VM de WSL sin depender del servicio enmascarado.

## 5. Catálogos DTE (Ministerio de Hacienda) — cargados completos

Antes: todas las tablas `dte_cat_*`, `dte_departamentos`, `dte_municipios`, `dte_tributos` estaban en 0 filas (los JSON del repo eran de ejemplo/incompletos, y faltaba por completo `cat_actividades_economicas.json`).

Se descargó el catálogo oficial vigente desde `factura.gob.sv` (Excel "Catálogos – Facturación Electrónica v1.1", actualizado 1 julio 2026, 33 catálogos CAT-001 a CAT-033) y se regeneraron los JSON en `ExportPlugins/sv-dte-laravel12/*/json/`, luego se corrió `php artisan db:seed --class=DteCatalogSeeder`.

| Tabla Stockia | Catálogo oficial | Filas |
|---|---|---|
| `dte_departamentos` | CAT-012 | 15 |
| `dte_municipios` | CAT-013 (distritos post-reforma 2021, no municipios viejos) | 45 |
| `dte_cat_tipo_establecimiento` | CAT-009 | 4 (¡se corrigió: 01=Sucursal/02=Casa Matriz, antes estaban invertidos!) |
| `dte_tributos` | CAT-015 | 47 |
| `dte_cat_actividades_economicas` | CAT-019 | 774 (antes no existía el archivo) |
| `dte_cat_014_unidades_medida` | CAT-014 | 40 |
| `dte_cat_022_tipos_documento` | CAT-022 (tipo doc. identificación) | 5 |
| `dte_cat_024_tipos_invalidacion` | CAT-024 (motivo del evento) | 3 |
| `dte_cat_tipos_dte` | CAT-002 (tipo documento/evento DTE) | 13 |
| `dte_cat_condicion_operacion` | CAT-016 | 3 |
| `dte_cat_tipo_modelo` | CAT-003 | 2 |
| `dte_cat_tipo_item` | CAT-011 | 4 |
| `dte_cat_tipo_operacion` | CAT-023 "Operaciones Especiales" | 2 — **⚠️ sin confirmar**: no existe un catálogo oficial llamado literalmente "Tipo de Operación"; este es el mejor mapeo por eliminación. Revisar contra el uso real en el código antes de confiar en él. |

Fuente: [factura.gob.sv — Documentos Técnicos](https://factura.gob.sv/informacion-tecnica-y-funcional/).

## 6. Estado actual de la app Stockia

- Empresa: **SUPER Y DISTRIBUIDORA QUEZALTECA** (NIT 0512-280887-101-3, NRC **23119-4** — ✅ resuelto 2026-08-13, era placeholder `0000-0`; el usuario confirmó el NRC real y se actualizó directo en `companies`)
- Bodega: **Principal** (código `MAIN-1`, Quezaltepeque)
- Usuario: LuisLatin (luislatinlflg@gmail.com), asignado a la empresa/bodega
- Aún en 0: `dte_settings`, `dte_correlativos`, `customers` (el legacy `pos.cliente` solo tiene 1 fila genérica — sin data real que migrar, se recrean frescos en Stockia)
- `products`: **1,617 filas** migradas desde `pos` (ver sección 7)

## 7. Migración de datos `pos` → `stockia` — implementada (2026-08-11)

Las 5 decisiones quedaron resueltas así, e implementadas en migraciones nuevas + `app/Console/Commands/ImportLegacyPosData.php` (comando `php artisan legacy:import-pos`, idempotente — usa `legacy_id` en cada tabla para poder re-ejecutarse sin duplicar):

1. **Multi-presentación:** se aplanó a una fila `product` por presentación, pero **solo contando presentaciones activas** (`activo=1`), con fallback a todas si un producto no tiene ninguna activa. Esto redujo el problema real de 97 productos a solo **3** con más de una fila — el análisis inicial (95 con 2, 2 con 3) contaba también presentaciones descontinuadas.
2. **Multi-precio:** se migra solo `precio` base a `products.price`. No se construyó ninguna función de niveles de precio (no había caso de uso concreto para ella).
3. **Categoría / Proveedor:** se crearon `categories` y `providers` (scoped a `company_id`, con `legacy_id` para trazabilidad), y `products.category_id`/`provider_id`. Estos modelos/tablas **no existían en absoluto** antes de esta sesión. Selects agregados a los formularios de crear/editar producto.
4. **`tipo_item`/`uni_medida`:** default `tipo_item=1` (Bienes), `uni_medida=59` (Unidad) para todos los productos migrados.
5. **`exento` → `afecto_iva`:** mapeo inverso directo, sin complicación (solo 1 de 1,614 productos era exento).

**Histórico de ventas:** las 96,134 facturas y 412,743 líneas de `pos` se migraron como solo-consulta a `legacy_sales`/`legacy_sale_items`, desacopladas de `sales`/DTE (son casi todas tipo `TICKET` no-fiscal — no tiene sentido re-emitirlas como DTE). No hay pantalla nueva para navegarlas, solo quedan en BD para reportes futuros.

**Resultado de la importación** (verificado con `Model::count()`, corrido dos veces para confirmar idempotencia):

| Tabla | Filas |
|---|---|
| `categories` | 37 |
| `providers` | 95 |
| `products` | 1,617 |
| `legacy_sales` | 96,134 |
| `legacy_sale_items` | 412,743 |

**Calidad de datos del legacy encontrada durante la importación** (no son bugs del import, son así en `pos`):
- `producto.codart` (candidato natural a SKU) es casi inútil: 1,495 de 1,614 productos comparten el valor placeholder `"1"`. Por eso casi ningún producto migrado tiene `sku` real — el `codigo` DTE cae a `barcode` o a `PRD-{id}`.
- La categoría `id_categoria=201` tenía `nombre_cat` vacío en el legacy → se nombró `"Categoría #201"`.
- ✅ **Resuelto 2026-08-13** — Dos productos ("AXION DISCO CAJA", "ZORRIK LAMINA DE AFEITAR") tenían dos presentaciones activas distintas con el mismo nombre de presentación ("CAJA"/"PAQUETE") pero distinto `unidad` — quedaban como 2 filas de apariencia idéntica en Stockia (ids 467/468 y 923/924). Se confirmó con el usuario que eran duplicados de captura del legacy: se reasignó el historial de `legacy_sale_items` de la fila con stock 0 (468→467, 924→923) y se eliminaron los productos 468 y 924. Se agregó además una restricción de nombre único por empresa (`products_company_id_name_unique`) para que no puedan volver a crearse duplicados de nombre.
- El stock inicial de las presentaciones secundarias (los 3 casos multi-fila) quedó en 0 — el legacy no reparte stock por presentación, solo por producto.

Para reconectar contra otra copia de `pos` o re-ejecutar: variables `LEGACY_DB_*` en `.env` (ver `.env.example`), conexión `legacy` en `config/database.php`.

## Archivos tocados en esta sesión

- [tailwind.config.js](tailwind.config.js) — `darkMode: 'class'`
- [.env](.env) — `DB_HOST=127.0.0.2`
- `../digital/pos/_conexion.php` — password vacío, host `127.0.0.2`
- `ExportPlugins/sv-dte-laravel12/catalogos-dte-acreditacion-completo-2026/json/*.json` — todos regenerados con data oficial
- `ExportPlugins/sv-dte-laravel12/catalogo-ubicaciones-sv-dte-2026/json/departamentos_municipios_sv_2026.json` — regenerado

## 8. Sesión 2026-08-12 — Arquitectura de módulos y 5 módulos de negocio nuevos

Trabajo distinto al de arriba (no es migración de entorno/datos, es evaluación y construcción de la arquitectura de módulos de Stockia). Resumen:

- Se evaluó qué le falta a Stockia frente al sistema legacy DigitalPos y se clasificó cada función faltante como Core (nativo, sin toggle) o módulo opcional.
- Se corrigió un antipatrón real: los toggles `sales` e `inventory` envolvían 100% funcionalidad Core sin nada opcional real — se extrajo ese Core a rutas siempre activas y ambos toggles ahora gatean contenido opcional genuino.
- Se construyeron 5 módulos/funciones nuevas: Cuentas por Pagar, Créditos y Cobros, Comisiones, Garantías, Bancos (módulo Contabilidad nuevo), y Traslados + Lotes/Estantes (Inventario Avanzado).
- Se agregó un mecanismo compartido de aviso "módulo no instalado" (antes las opciones desaparecían en silencio del menú).
- Todo quedó commiteado localmente (`git log` — commit `feat: fix Core/module toggle architecture and add 5 new business modules`). **No se hizo push** (repo es solo local, ver instrucciones del usuario).

**Para retomar de esa sesión:** ver [`MODULOS.md`](MODULOS.md) — mapa completo de qué es Core vs. qué agrega cada módulo y referencia técnica de dónde vive cada pieza.

## 9. Sesión 2026-08-13 — Productos, auditoría de rendimiento, Apartados, Libros IVA

Trabajo distinto al de arriba también. Resumen:

- **Productos:** se limpiaron los 2 duplicados de captura del legacy (ver sección 7 arriba, ✅ resuelto) y se agregó restricción de nombre único. Se rehizo el listado: paginación, orden por columna (nombre/precio/stock), filtro de disponibilidad, y búsqueda en vivo por AJAX.
- **NRC de la empresa:** ✅ resuelto (ver sección 6 arriba) — ya no es placeholder.
- **Auditoría de rendimiento de toda la app:** 20 hallazgos (N+1, consultas muertas, falta de paginación, índices faltantes) — los 20 quedaron corregidos, incluyendo el N+1 de garantías en el checkout y el índice compuesto de `inventory_movements`.
- **Apartados de clientes** (nuevo, dentro de `packages/sales-quotes`): reserva de mercadería con entrega futura; descuenta stock de inmediato y lo libera si se anula.
- **Libros Fiscales IVA** (nuevo, `DteVatBooksController`): Ventas a Contribuyente, Ventas a Consumidor (resumen diario) y Compras, con export CSV. Al construir el libro de Compras se descubrió que `PurchaseController` nunca capturaba IVA real (siempre `iva=0`, `total=subtotal`) — se corrigió derivando gravado/exento del `afecto_iva` de cada producto, sin agregar campos al formulario. El formato de columnas se basó en investigación web del Anexo de Compras/Ventas de Hacienda, pero **no ha sido validado por un contador real** — pendiente antes de usarlo como libro oficial de presentación.
- **Revisión de empaquetado de módulos:** ver [`MODULOS.md`](MODULOS.md) sección 6 — solo `ptv-pos` y `sales-quotes` son paquetes Composer reales y autocontenidos; `sales`, `inventory`, `accounting` y `dte-sv-mh` siguen siendo solo un interruptor (`modules` + `ModuleGateServiceProvider`) con el código mezclado directo en `app/`. El mapa archivo-por-archivo de qué movería cada uno a un paquete real ya está documentado, pero **no ejecutado** — es un refactor de +30 archivos, quedó como la siguiente decisión, no como tarea de una sesión.
- Todo quedó commiteado localmente en 5 commits (`perf: fix N+1...`, `perf: close remaining audit items...`, `feat: add customer layaway orders...`, `docs: map which module files...`). **No se hizo push.**

**Pendiente sin resolver, en el radar para la próxima sesión (por tamaño):**
1. ✅ **Resuelto 2026-08-13** — Los 3 ZIP de `ExportPlugins/` (`ptv-pos.zip`, `sales-quotes.zip`, `sv-dte-laravel12.zip`) se regeneraron desde el código fuente actual (confirmado: `sales-quotes.zip` ya incluye Apartados) y quedaron listos para commitear. No existía un script para esto — el proceso manual con `Compress-Archive` quedó documentado en [`MODULOS.md`](MODULOS.md) sección 6 para no tener que redescubrirlo la próxima vez.
2. ✅ **Resuelto 2026-08-13/14** — `sales`, `inventory`, `accounting` y `dte-sv-mh` ya son paquetes Composer reales bajo `packages/`. Ver sección 10 abajo y `MODULOS.md` sección 6 para el detalle completo.
3. Que un contador valide el formato exacto de columnas de los Libros IVA contra la plantilla oficial vigente de Hacienda. Sigue pendiente — requiere revisión humana, no es tarea de código.
4. ✅ **Resuelto 2026-08-14** — Bug encontrado durante la extracción de `dte-sv-mh` (ver sección 10): `DteEmissionService::emitForSale()`/`resendContingency()` fallaban en modo `simulacion`/`real` al insertar `response_payload` (array PHP crudo) en una columna JSON de `dte_send_attempts`. Se envolvió en `json_encode()` en ambos lugares; verificado con una venta de prueba en modo `simulacion` (llega a `estado: ACEPTADO`).

**Para retomar de esta sesión:** ver [`MODULOS.md`](MODULOS.md) secciones 4 y 6.

## 10. Sesión 2026-08-13/14 — Empaquetado completo: los 6 módulos son paquetes reales

Continuación directa del punto 2 pendiente de la sección 9. Resumen:

- **`accounting`, `inventory`, `sales`, `dte-sv-mh`** se extrajeron de `app/` a `packages/<nombre>/` (composer.json, `src/`, migraciones, vistas, rutas y Service Provider propios), siguiendo el mismo patrón que ya usaban `ptv-pos`/`sales-quotes`. Orden: de menor a mayor acoplamiento con Core.
- **Hallazgo de diseño importante:** `DteCalculationService` (cálculo de IVA 13%) no es parte opcional de DTE — `SaleController::store()` lo usa para el total de cualquier venta. Se decidió que el cálculo de IVA se queda permanentemente en Core (incluida la migración `prepare_dte_core_schema` que agrega las columnas fiscales); solo la integración con el Ministerio de Hacienda (emisión, correlativos, panel admin, libros IVA, clientes DTE) se empaquetó como lo opcional/toggleable.
- **Bug de gating arreglado:** ni `SaleController` ni `packages/ptv-pos`'s `PTVPosController` chequeaban `Module::isEnabled('dte-sv-mh')` antes de emitir el DTE — desactivar el módulo no hacía nada. Ambos ahora sí lo chequean, y se agregó la entrada faltante en `ModuleGateServiceProvider::GATED_PREFIXES`.
- **Bug preexistente encontrado, no arreglado** (fuera de alcance): en modo `simulacion`/`real`, `DteEmissionService` intenta insertar un array PHP sin `json_encode()` en la columna JSON `dte_send_attempts.response_payload` — MySQL lo rechaza. Nunca se había disparado antes porque no había ventas reales en la BD de dev. Verificado con una venta de prueba en modo `contingencia` (que sí funcionó de punta a punta) y confirmado el bug con una venta de prueba en modo `simulacion`.
- **Limpieza:** el patrón intermedio `app/Modules/<Nombre>` (usado antes de que estos 4 módulos tuvieran paquete propio) quedó huérfano y se eliminó por completo.
- Verificado end-to-end por fase: rutas registradas con el nuevo namespace, `php artisan view:cache` compila las ~200 vistas sin error, hooks `Sale::booted()`/`SaleItem::booted()` siguen generando `Commission`/`Warranty` correctamente, catálogos DTE se re-seedean desde su nueva ruta empaquetada (`packages/dte-sv-mh/database/data/dte/`), emisión de DTE probada de punta a punta en transacciones revertidas (sin dejar residuo en la BD de dev).
- Los 4 ZIP nuevos/actualizados (`accounting.zip`, `inventory.zip`, `sales.zip`, `dte-sv-mh.zip`, más `ptv-pos.zip` reexportado porque también se tocó) quedaron listos para commitear.

**Para retomar de esta sesión:** ver `MODULOS.md` sección 6 para el detalle completo de qué se movió y qué se quedó en Core, y el punto 4 de la lista de pendientes arriba para el bug de `dte_send_attempts` sin arreglar.

## 11. Sesión 2026-08-14 — Auditoría de completitud contra DigitalPos

Con los 6 módulos ya empaquetados, se leyó el código real de `digital/pos/` (no solo nombres de archivo, sino las consultas SQL y la lógica de cada pantalla) para confirmar que Stockia no le falte ninguna función real del legacy. Ver `MODULOS.md` sección 4bis para el detalle completo.

**Resultado: prácticamente completo.** Se investigaron 3 grupos de pantallas sin equivalente obvio en Stockia y se descartaron 2 como falsos positivos (Pedidos a Proveedores es un documento paralelo sin integración real ni siquiera en el propio DigitalPos; Asignación/Reasignación es el mismo mecanismo que ya cubre el módulo `inventory`).

**Un gap real, documentado y no construido (decisión del usuario 2026-08-14):** la "Autorización de precio" de DigitalPos — un código de un solo uso que un supervisor genera para subir temporalmente el techo de nivel de precio de un vendedor en una venta puntual. Requiere primero los niveles de precio por producto que se descartaron deliberadamente durante la migración de datos (sección 7, punto 2, "no había caso de uso concreto"). No se sabe si esta función se usó realmente en la operación de Quezalteca — queda pendiente de decisión de negocio, no de código.

También se investigó a fondo el módulo de Usuarios de DigitalPos (`usuario`/`empleado`/`vendedor`/`permiso_usuario`) contra el de Stockia (`User`+`company_user.role`). Hallazgo principal: DigitalPos tiene una grilla de permisos por pantalla (`menu`→`modulo`, 129 filas→`usuario_modulo`), algo que Stockia no tenía — el usuario decidió construirlo (ver sección 12).

## 12. Sesión 2026-08-14 — Permisos granulares por pantalla

Cierra el gap encontrado en la sección 11. Ver `MODULOS.md` sección 7 para el detalle técnico completo. Resumen de decisiones y hallazgos:

- **Granularidad elegida:** un permiso por pantalla de menú (~27 claves, algunas colapsadas cuando varias pantallas ya comparten el mismo gate de rol — p. ej. las 5 pantallas de POS bajo una sola clave `ptvpos`), no por controlador (32) ni por acción CRUD individual como DigitalPos (~150).
- **Modelo elegido:** el rol (Vendedor/Admin/SuperAdmin) sigue siendo el default; los permisos son excepciones por usuario, aditivas o sustractivas — sin ninguna excepción configurada el comportamiento es idéntico a hoy.
- **Hallazgo de diseño que simplificó todo:** `resources/views/layouts/partials/sidebar.blade.php` ya traía soporte sin usar para `$item['can']` (autorización nativa de Laravel), así que el sistema se construyó sobre `Gate::define()` + el middleware `can:` que Laravel ya trae por defecto, en vez de inventar una capa propia — cero cambios a las plantillas del sidebar.
- **`users` y `settings-modules` quedaron fuera de la capa de excepciones a propósito** — barrera anti-escalamiento de privilegios (nadie puede usar el sistema de permisos para darse a sí mismo acceso a administrar usuarios o módulos).
- **2 hallazgos independientes de la auditoría de rutas, arreglados en el mismo trabajo:** `currencies.index`/`inventory.index`/`sale_items.index` vivían fuera del grupo `auth` en `routes/web.php` (accesibles sin sesión) — movidas adentro; `app/Http/Middleware/RoleMiddleware.php` era código muerto — eliminado.
- **Efecto colateral bueno:** varios ítems de `config/sidebar.php` (Productos, Categorías, Compras, Cuentas por Pagar, Proveedores, Administración Central, Usuarios) no tenían `roles` pese a que su ruta sí exigía Admin/SuperAdmin — un Vendedor los veía en el menú aunque no podía usarlos. Quedó corregido al agregar `can`/`roles` basados en el gate real de cada ruta.
- Verificado en tinker (transacciones revertidas): `Permission::userCan()` respeta rol por defecto, excepción `allowed=true` y excepción `allowed=false`; `Gate::has()` confirma que `users`/`settings-modules` nunca se registran como Gate; `route:list -v` confirma el middleware `can:<clave>` en cada grupo afectado (core + los 6 paquetes); las 3 rutas movidas ya exigen `auth`; `php artisan view:cache` compila todas las vistas sin error.
- Los 6 ZIP de `packages/` se reexportaron porque todos tuvieron cambios en sus `routes/web.php`.

## 13. Sesión 2026-08-14 — Bug post-empaquetado: `accounting`/`inventory`/`sales` no cargaban

El usuario reportó que Bancos y Cuentas no cargaban. Causa raíz: al extraer estos 3 módulos a paquete real (sección 10), sus `routes/web.php` se copiaron con el middleware tal cual venía del wrapper viejo `app/Modules/<Nombre>` — ese wrapper (`App\Modules\Core\Providers\ModuleServiceProvider`, ya eliminado) aplicaba `Route::middleware('web')->group($file)` **por fuera** del archivo de rutas, así que el archivo mismo nunca necesitó declarar `'web'`. Al pasar a `loadRoutesFrom()` (el mismo patrón que ya usaban bien `ptv-pos`/`dte-sv-mh`/`sales-quotes`), ese `'web'` implícito se perdió — sin él, `ShareErrorsFromSession` nunca corre y cualquier vista con `$errors->any()` (es decir, todas) explota con "Call to a member function any() on null".

- **No se detectó en las verificaciones de la sección 10** porque esas pruebas fueron con `tinker` llamando clases directamente (`Module::isEnabled()`, `Permission::userCan()`), nunca simulando una petición HTTP real a través de todo el pipeline de middleware — un gap real en cómo se verificó ese trabajo.
- **Arreglado:** se agregó `'web'` al arreglo de middleware en los 3 archivos. Verificado simulando peticiones HTTP completas (`app()->handle($request)` con sesión/auth reales, no solo `route:list`) contra las 27 rutas principales de los 6 paquetes + core — todas `200` limpio.
- Los 3 ZIP (`accounting.zip`, `inventory.zip`, `sales.zip`) se reexportaron.

**Pendiente sin resolver, prioridad para la próxima sesión (decisión del usuario 2026-08-14):**
1. **Cobertura de pruebas automatizadas** — este bug es la prueba de que hace falta. Un smoke-test mínimo (Pest/PHPUnit) que golpee las rutas principales de cada módulo, por rol, habría cachado el `'web'` faltante solo. Candidato: empezar por un test de feature simple por cada uno de los 6 paquetes + rutas core, corriendo como cada rol (Vendedor/Admin/SuperAdmin) y confirmando `200`/redirect esperado.
2. **Vendedor ligado a Cliente** — gap real de la sección 11 (auditoría de Usuarios contra DigitalPos), aún sin construir: un rol de representante externo atado a un cliente específico (`vendedor`+`usuario` emparejados en DigitalPos), modelo mayorista/distribuidor. Relevante si Quezalteca reparte con vendedores de ruta asignados a clientes fijos — confirmar con el negocio antes de diseñar.

**Otros pendientes ya documentados, sin prioridad asignada aún:**
- Que un contador valide el formato de columnas de los Libros IVA (sección 9) — no es tarea de código.
- Ficha de empleado (RRHH: DUI, salario, tipo) y los reportes de DigitalPos no construidos (costo/utilidad, reposición, entradas/salidas) — prioridad baja, ver sección 11.
- Autorización de precio / niveles de precio (sección 11) — decisión explícita del usuario de no tocarlo por ahora.

**Para retomar:** empezar por el punto 1 (pruebas) ya que protege todo lo construido hasta ahora; luego el punto 2 si el negocio confirma que aplica el modelo de vendedor de ruta. Ver `MODULOS.md` secciones 4bis, 6 y 7 para el contexto técnico completo de todo lo construido en las últimas 2 sesiones.

## 14. Sesión 2026-11-18 — API REST (Postman/Swagger)

Trabajo nuevo, no es continuación de lo de arriba. Hasta esta sesión, Stockia prácticamente no tenía API: `routes/api.php` era un solo endpoint roto (Sanctum no estaba instalado). Se construyó de cero:

- **Sanctum real** + puente de contexto (`ResolveApiCompanyContext`) que deja empresa/bodega en `session()` por request a partir de headers `X-Company-Id`/`X-Warehouse-Id` — así todo el código existente (scoping por empresa, permisos, accessors de stock) funciona igual para web y API sin tocarlo.
- **Swagger/OpenAPI** vía `dedoc/scramble` (`/docs/api`), importable directo en Postman.
- **API de núcleo**: Productos y Ventas (`routes/api.php`). La creación de ventas se extrajo a `app/Actions/Sales/CreateSale.php`, usada por web y API por igual (único refactor del plan).
- **API de un paquete real** (`ptv-pos`): estado de caja + abrir caja, como prueba de que el mismo molde funciona dentro de un módulo gateado por `Module::isEnabled()`.
- 2 bugs reales encontrados y arreglados en el camino: scoping por vendedor faltante en ventas de la API, y el rate limiter `api` (nunca se había registrado en este proyecto).
- Todo verificado a mano contra la BD real de Quezalteca con datos de prueba creados y borrados al final — no quedó nada de prueba en `products`/`sales`/`pos_sessions`/`pos_registers`, y los 6 módulos siguen igual que antes (todos desactivados).

**Para retomar:** ver [`HANDOFF_API.md`](HANDOFF_API.md) — tiene el detalle técnico completo, cómo probar cada endpoint, y la lista priorizada de qué sigue (cerrar caja/checkout por API en ptv-pos, extender el molde a los otros 5 paquetes, CORS/expiración de tokens antes de exponerlo a un cliente externo real).

## 15. Sesión 2026-11-18 — Instancia de evaluación (compartida por internet)

Objetivo: dejar una copia funcionando para que un compañero la evalúe y pida cambios. **No es producción.**

> **Actualización (mismo día): la instancia se movió de WSL a un servidor Ubuntu en la LAN** porque WSL consumía demasiada memoria en la PC de desarrollo. La instancia de WSL descrita abajo queda **apagada** (no borrada). Lo vigente es la sección 15.1.

### 15.1 Servidor Ubuntu 24.04 — `192.168.16.191` (vigente)

- Acceso: SSH `soportecorp@192.168.16.191` (llave dedicada en la PC de desarrollo: `~/.ssh/id_ed25519_stockia`; usar el `ssh` de Git Bash). `sudo` pide contraseña → los pasos de root los corre el usuario.
- App en **`/var/www/stockia`**, clonada de GitHub siguiendo `master`. PHP **8.3 nativo** de 24.04 (aquí `php` y `composer` del sistema sirven, sin PPA), Apache en el **puerto 80** → php8.3-fpm, MariaDB (`stockia`, contraseña en `~/.stockia-db-pass`), Node 20.
- Mismo vhost que en WSL: `HTTPS=on` desde `X-Forwarded-Proto` y `/register` bloqueado.
- Datos: dump de Laragon del 2026-11-18 (copia real de Quezalteca), los 6 módulos activos, `DTE_MODE=simulacion`. El dump y el `.env` subidos para importar se borraron del servidor al terminar.
- **Túnel como servicio systemd** `stockia-tunnel` (quick tunnel, arranca solo con el servidor). La URL cambia en cada reinicio del servicio; obtenerla con:
  ```bash
  journalctl -u stockia-tunnel --no-pager | grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' | tail -1
  ```
  Detener/arrancar: `sudo systemctl stop|start stockia-tunnel`.
- Scripts en `deploy/server/`: `setup-root.sh` (sudo: paquetes, BD, vhost, servicio del túnel) e `install-app.sh <dump.sql> <.env origen>` (sin sudo: `.env`, importación, módulos, cache, prueba HTTP). Orden: `setup-root.sh` → `git clone` + `composer install --no-dev` + `npm ci && npm run build` → `install-app.sh` → `sudo systemctl start stockia-tunnel`.
- Actualizar con cambios de `master`: en `/var/www/stockia`, `git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build && php artisan migrate --force && php artisan optimize`.

### 15.2 WSL Ubuntu-22.04 (apagada, histórico)

**Dónde y cómo corre**
- WSL `Ubuntu-22.04`, carpeta `~/proyectos/stockia`, clonada de GitHub y **siguiendo `master`**.
- PHP **8.3** (php8.3-fpm, PPA ondrej) instalado junto al PHP 8.1 por defecto del sistema → siempre llamar `php8.3` explícito. El `composer` del PATH es el de Windows (`/mnt/c/composer`); usar `php8.3 /mnt/c/composer/composer.phar`.
- Apache, vhost propio en el puerto **8081** → php8.3-fpm. El puerto 80 de esa misma WSL es otro proyecto (no tocar).
- MariaDB, BD y usuario `stockia`; la contraseña vive en `~/.stockia-db-pass` (fuera del repo).
- Acceso público con **Cloudflare quick tunnel**: `cloudflared tunnel --url http://localhost:8081`. La URL (`*.trycloudflare.com`) es aleatoria y **cambia cada vez que se reinicia** el túnel; hay que volver a compartirla.

**Datos y configuración**
- Datos: **copia de la BD real de Quezalteca** (dump de Laragon del 2026-11-18, decisión explícita del usuario). Se importó sin cambios de esquema (91 tablas).
- `.env` copiado del de Windows, reutilizando el mismo `APP_KEY` (necesario para descifrar datos cifrados), con `APP_ENV=production`, `APP_DEBUG=false`.
- **Los 6 módulos están activados en esta copia** (en la BD de Laragon siguen todos desactivados). El DTE sigue en `DTE_MODE=simulacion`, ambiente `00`, sin credenciales de MH → no se emite nada real a Hacienda.
- El vhost hace dos cosas solo para esta instancia, sin tocar código: pone `HTTPS=on` cuando llega `X-Forwarded-Proto: https` (la app no tiene `trustProxies`, sin esto los assets salen como `http://` y se rompen tras el túnel) y **bloquea `/register`** (403). Los usuarios se crean desde Usuarios → Crear.
- `/docs/api` (Swagger) da 403 porque Scramble lo cierra en `production`.
- `MAIL_MAILER=log` → "¿Olvidaste tu contraseña?" no envía nada. Resetear contraseñas con `deploy/wsl/reset-pass.sh <email>`.
- Crear usuarios dummy: el correo solo se valida por formato (cualquier dominio sirve) y no hay verificación de cuenta (`MustVerifyEmail` desactivado). Usar dominios inexistentes (`.test`, `example.com`).

**Scripts** (`deploy/wsl/`, se corren desde Windows con `wsl -d Ubuntu-22.04 --exec bash /mnt/c/laragonOld/www/Stockia/deploy/wsl/<script>`; los de root con `sudo bash ...` desde una terminal de Ubuntu porque `sudo` pide contraseña):
1. `setup-root.sh` (sudo) — PHP 8.3, MariaDB + BD/usuario, Node 20, cloudflared, vhost :8081. Agrega el PPA a mano porque la API de Launchpad (`add-apt-repository`) dio 503.
2. `vhost-tunnel.sh` (sudo) — reescribe el vhost con `HTTPS=on` + bloqueo de `/register`. Correr después del 1.
3. `import-db.sh <dump.sql>` — importa el dump (convierte collations `utf8mb4_0900_*` de MySQL 8 a MariaDB si aparecen) y escribe `DB_PASSWORD` en `.env`.
4. `finalize.sh` — permisos de `storage`, `storage:link`, `migrate --force`, `optimize`, prueba HTTP.
5. `modules.sh [enable]` — muestra (o activa todos) los módulos y los usuarios.
6. `reset-pass.sh <email>` — contraseña aleatoria nueva para ese usuario.

Gotcha: `wsl -d X -- bash -lc '... $VAR ...'` expande las variables en el shell de afuera (llegan vacías). Usar `--exec bash script.sh`.

**Actualizar la instancia cuando haya cambios en `master`** (dentro de `~/proyectos/stockia`):
```bash
git pull
php8.3 /mnt/c/composer/composer.phar install --no-dev --optimize-autoloader
npm ci && npm run build
php8.3 artisan migrate --force && php8.3 artisan optimize
```

**Riesgo a recordar:** cualquiera con la URL del túnel ve el login de una copia con datos reales. Compartirla solo con quien evalúa y detener `cloudflared` cuando no se use. Si se publica en un servidor real detrás de proxy, agregar `trustProxies` en `bootstrap/app.php` en lugar del truco del vhost.

**Pendiente nuevo (detectado al revisar roles para las pruebas):**
- **Comisiones de un Admin que vende no se ven.** Un Admin/SuperAdmin puede vender (ventas, POS y cotizaciones aceptan los 3 roles). Si tiene `comision_porcentaje` asignado en la empresa, `Sale::booted()` ([`app/Models/Sale.php`](app/Models/Sale.php)) **sí genera** su registro de comisión, pero `CommissionController::index()` ([`packages/sales/src/Http/Controllers/CommissionController.php`](packages/sales/src/Http/Controllers/CommissionController.php)) solo lista usuarios con rol `Vendedor` → esas comisiones quedan en la BD sin poder verse ni pagarse desde la pantalla. Arreglo probable: listar a todo usuario de la empresa con `comision_porcentaje > 0` o con comisiones registradas, no filtrar por rol. Confirmar con el negocio si un Admin debe cobrar comisión.
- Nota relacionada (no es bug): el filtro "solo ves tus propias ventas" aplica solo al rol `Vendedor`; un Admin ve todas. Para evaluar la experiencia de vendedor usar un usuario con rol `Vendedor`.
