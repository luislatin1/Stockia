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
