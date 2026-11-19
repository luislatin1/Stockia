# Fase 1 — Núcleo fiscal: avance

Rama `fase1-dte-v2` · punto de retorno: etiqueta `pre-fase1-dte` (commit `15193f9`) · respaldo de BD en `storage/app/backups/` (ignorado por git, contiene datos reales de cliente: no copiar fuera).

## Cómo volver atrás
- Código: `git checkout master` (o `git checkout pre-fase1-dte`).
- BD: `php artisan migrate:rollback --step=N` revierte las migraciones nuevas, o restaurar el volcado de `storage/app/backups/`.
  Ojo: `down()` de la migración de secretos **no** descifra `api_password` a propósito.

## Qué se hizo
| Tarea | Estado | Dónde |
|---|---|---|
| 1.1 Establecimientos y puntos de venta | ✅ | tablas `dte_establecimientos`, `dte_puntos_venta`; modelos `DteEstablecimiento`/`DtePuntoVenta`; pantalla **DTE > Establecimientos**; vínculo opcional bodega→establecimiento y caja POS→punto de venta; relleno provisional `M001`/`P001` por empresa existente |
| 1.2 Distrito | 🟡 | columnas `companies.distrito`, `customers.distrito`, `dte_establecimientos.distrito`; catálogo `dte_distritos` (262, CAT-008); selectores en empresa, cliente y establecimiento. **Pendiente:** jerarquía distrito→municipio |
| 1.3 Numeración | ✅ | `DteNumberingService`: `DTE-TT-<M001><P001>-<15 díg.>`, centralizada o por establecimiento, nunca por PV ni tipo, reinicio por ejercicio en hora de El Salvador, con bloqueo transaccional. Reemplaza a `DteCorrelativeService` (eliminado; la tabla `dte_correlativos` se conserva sin uso) |
| 1.4 Secretos | ✅ | `api_password` y `signer_password` cifrados (`encrypted`), ocultos al serializar y **nunca impresos en el HTML**; contraseña en blanco = conservar. El certificado ya no vive en Stockia (un firmador por cliente) |
| 1.5 Catálogos versionados | 🟡 | tabla `dte_catalogo_versiones` + registro de CAT-008 (versión, fuente, filas, hash). Los demás catálogos aún sin registrar; siguen existiendo dos fuentes JSON paralelas |
| 1.6 Impuestos con vigencia | ✅ | tabla `tax_rates` (IVA 13 %, retención 1 %, percepción 1 %, pago a cuenta 1.75 %; **validar con el contador**), `TaxRateService`, y `DteCalculationService` ya no usa constantes |

Salvaguarda añadida: en **modo real** no se emite si el establecimiento o el punto de venta no están marcados como *confirmados por Hacienda*.

## Verificación
- 44 tests pasan (23 previos + 21 nuevos: numeración, vigencia de tasas, cifrado, distritos, pantallas, aislamiento entre empresas).
- Migraciones probadas sobre una **copia** de la BD real: correctas, con relleno `M001`/`P001`. Emisión simulada de punta a punta: `DTE-01-M001P001-000000000000001` y `…02` (31 caracteres); el modo real se niega con códigos provisionales. Copia eliminada.
- **La BD de desarrollo (`stockia`) no se migró** (ver abajo).

## Para aplicar en una instalación
`php artisan migrate`. Las migraciones del paquete DTE **solo se cargan con el módulo `dte-sv-mh` activado** (diseño existente de `DteSvMhServiceProvider`): en la BD de desarrollo todos los módulos están en `enabled=0`, así que ahí solo correrían las 2 migraciones del núcleo. Activar el módulo y luego migrar.

## Servidor de evaluación (192.168.16.191)
Desplegado el 2026-11-19 sobre la instancia compartida: **ahora sigue la rama `fase1-dte-v2`, no `master`** (commit `3bf41d9`). Migraciones aplicadas sin errores; emisión simulada verificada ahí (con rollback).
- Punto de retorno del servidor: commit previo `eab8faf` (guardado en `~/backups/head_pre-fase1.txt`) y respaldo `~/backups/stockia_pre-fase1_20261119_2218.sql`.
- Volver: `git checkout master` en `/var/www/stockia`, restaurar el respaldo y `php artisan optimize`.
- Próximos despliegues: `bash deploy/server/update.sh [rama]` (respaldo + pull + composer + build + migrate).
- Falta Docker en el servidor (necesario para el firmador en la Fase 2; requiere sudo).

## Pendiente de la Fase 1
1. **Jerarquía distrito→municipio** (`dte_distritos.municipio_codigo` está en NULL). Mientras tanto la UI filtra por departamento y Hacienda validará la combinación.
2. **Códigos reales** de establecimiento y punto de venta de cada cliente, y marcarlos como confirmados.
3. Validar tasas de `tax_rates` con el contador.
4. 1.5: registrar versión de los demás catálogos y unificar las dos fuentes JSON.
5. `companies.certificado_firma`, `use_dummy_certificate` y los campos `static_*`/`dummy_*` quedan sin uso real: retirarlos en Fase 2 (reescritura de firma y envío).
6. Hallazgo para Fase 2: `fecEmi`/`horEmi` usan `now()` en **UTC**; después de las 18:00 en El Salvador salen con el día siguiente. Debe usar la zona `dte.timezone`.
