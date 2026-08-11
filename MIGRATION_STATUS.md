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

- Empresa: **SUPER Y DISTRIBUIDORA QUEZALTECA** (NIT 0512-280887-101-3, NRC 0000-0 — parece placeholder, revisar si es el NRC real)
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
- Dos productos ("AXION DISCO CAJA", "ZORRIK LAMINA DE AFEITAR") tienen dos presentaciones activas distintas con el mismo nombre de presentación ("CAJA"/"PAQUETE") pero distinto `unidad` — quedaron como 2 filas de apariencia idéntica en Stockia. Revisar manualmente si son de verdad dos variantes distintas o un duplicado de captura.
- El stock inicial de las presentaciones secundarias (los 3 casos multi-fila) quedó en 0 — el legacy no reparte stock por presentación, solo por producto.

Para reconectar contra otra copia de `pos` o re-ejecutar: variables `LEGACY_DB_*` en `.env` (ver `.env.example`), conexión `legacy` en `config/database.php`.

## Archivos tocados en esta sesión

- [tailwind.config.js](tailwind.config.js) — `darkMode: 'class'`
- [.env](.env) — `DB_HOST=127.0.0.2`
- `../digital/pos/_conexion.php` — password vacío, host `127.0.0.2`
- `ExportPlugins/sv-dte-laravel12/catalogos-dte-acreditacion-completo-2026/json/*.json` — todos regenerados con data oficial
- `ExportPlugins/sv-dte-laravel12/catalogo-ubicaciones-sv-dte-2026/json/departamentos_municipios_sv_2026.json` — regenerado
