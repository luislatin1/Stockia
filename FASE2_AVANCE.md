# Fase 2 — Facturación DTE v2: avance

Rama `fase1-dte-v2` · desplegada en el servidor de evaluación · detalle de la Fase 0 en `FASE0_HALLAZGOS.md`, Fase 1 en `FASE1_AVANCE.md`.

## Hecho

### 2.0 Identificadores fiscales (NIT / DUI / NRC)
- La Normativa v2 exige **sin guiones**: NIT de 9 o 14 dígitos, DUI de 9 dígitos, NRC sin guion ni ceros a la izquierda.
- Decisión: **sin columna duplicada**. La base conserva el formato con guiones (lo que ve e imprime el usuario) y el DTE usa la versión derivada con `App\Support\FiscalId`. Los modelos `Company`, `Customer` y `Provider` guardan con guiones lo válido y no tocan lo dudoso.
- Consumidor final sin identificar: receptor `null` (antes se enviaba un DUI inventado `00000000-0`).
- Auditoría de datos: `php artisan dte:revisar-identificadores`.

### 2.1 Esquemas oficiales versionados
- 15 esquemas de Hacienda en `packages/dte-sv-mh/resources/schemas/` con `manifest.json` (versión, sha256, `vigente_desde`). Corrección: son **15**, no 17 como se dijo antes.
- `DteSchemaRegistry` elige la versión por argumento, por `config('dte.esquemas')` o por fecha de vigencia (hora de El Salvador); permite convivir con v1.2 en la transición.
- `DteSchemaValidator` (opis/json-schema, draft-07) da un informe completo en español, con la ruta de cada campo. `DteSchemaException` bloquea la firma.
- Comando `php artisan dte:esquemas` (lista, verifica integridad, valida archivos o DTE guardados).
- 84 tests pasan en total.

## Brecha medida (JSON actual de Stockia vs. esquema oficial Factura v2)
Medida en el servidor con datos reales (venta simulada, con rollback): **29 errores**.

| Grupo | Errores |
|---|---|
| Versión enviada = 1 (debe ser 2) | `identificacion.version` |
| Secciones requeridas ausentes | `documentoRelacionado`, `otrosDocumentos`, `ventaTercero` |
| Emisor | falta `distrito`, `codEstable`, `codPuntoVenta`; `telefono` y `correo` salen `null`; sobra `tipoEstablecimiento` |
| Receptor | faltan `codActividad`, `descActividad`, `distrito`; la dirección debe ser `null` completa, no un objeto con campos `null` |
| Cuerpo | falta `ivaItem`; `tributos` debe traer al menos un elemento |
| Resumen | faltan `ivaRete`, `totalNoGravado`, `totalIva`, `saldoFavor`, `condicionOperacion`, `pagos`, `numPagoElectronico`, `observaciones`; sobran `ivaRete1`, `reteRenta` |
| Raíz | sobra `extension` |

**Dato de la empresa real:** su `telefono` y `correo` están vacíos y el esquema los exige (teléfono de 8 a 30 caracteres, correo de 6 a 100). Hay que capturarlos en Administración Central, igual que el distrito.

## Hallazgos de esquema útiles para el constructor
- `emisor.codEstable` (4 caracteres) y `codPuntoVenta` (hasta 15) son códigos **internos opcionales**, distintos de los `M001`/`P001` de Hacienda que van en el número de control.
- Por regla de la Normativa, los esquemas son "guía estructural referencial": además hay validaciones de cálculo y de catálogo (Anexo IV) que Hacienda ejecuta y que no cubre el esquema.

## Siguiente
1. **2.2 Constructor DTE v2** (Factura 01 y CCF 03 primero): cierra los 29 errores, incluyendo cálculo con 8 decimales por ítem y 2 en resumen, `totalLetras` real, fecha/hora en hora de El Salvador, identificación obligatoria sobre el monto legal y `condicionOperacion`/`pagos`.
2. Probarlo contra las 412.743 líneas históricas.
3. Firma (firmador oficial por cliente), transmisión, reintentos y eventos.

## Pendiente de terceros
- Confirmar con el contador el **monto legal** a partir del cual el receptor debe identificarse.
- Códigos reales de establecimiento/punto de venta, distrito→municipio, credenciales y certificado del ambiente de pruebas, Docker en el servidor.
