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

### 2.2 Constructor DTE v2 (Factura 01 y CCF 03) — hecho 2026-11-20
- `Stockia\DteSvMh\Builders\DteV2Builder`: función pura (sin Eloquent) que recibe datos normalizados y devuelve el DTE. `DteEmissionService::buildPayload` solo arma el contexto desde la venta y lo invoca; después valida contra el esquema oficial (`DteSchemaValidator::asegurar`) **antes** de crear el registro y firmar.
- Factura: versión JSON 2, precios **con IVA**, `ivaItem` = (ventaGravada/1,13)×13 % por ítem, `totalIva` en resumen, sin tributos. CCF: versión JSON **4**, precios **sin IVA**, tributo `['20']` por ítem y detalle en el resumen, `ivaPerci`, `ivaRete`.
- Cuerpo a 8 decimales, resumen a 2 calculado desde el ítem; `totalLetras` real; fecha/hora en hora de El Salvador; `tipoModelo`/`tipoOperacion` 1 (normal) o 2 (contingencia); `condicionOperacion` 1/2 y `pagos` (contado: forma `01`; crédito: sin forma, monto 0, plazo `01` días + periodo = días de crédito).
- Receptor: Factura admite `null` (consumidor final); CCF exige NIT, NRC, actividad, dirección. Emisor con `distrito`, `codEstable`/`codPuntoVenta` (códigos internos M001/P001).
- Migración nueva: `customers.cod_actividad` y `desc_actividad` (el CCF los exige). Falta captarlos en el formulario de clientes.
- 93 tests pasan (9 del constructor, todos validan contra el esquema oficial).

**Supuestos por cotejar con el Anexo IV/catálogos (no verificados contra el PDF):**
- CAT-017 forma de pago `01` = billetes y monedas; CAT-018 plazo `01` = días. Stockia solo distingue efectivo/crédito; tarjeta, transferencia, etc. salen como efectivo hasta que existan en el POS.
- Los totales del DTE se recalculan desde el ítem (precio × 1,13); pueden diferir centavos de `sales.total`. El DTE manda para Hacienda.
- Retención de renta: se rechaza la emisión si es > 0 (el esquema de Factura/CCF no la tiene). Percepción de IVA: siempre 0.
- Solo 01 y 03. Faltan 05, 06, 11, 14 (FEX v3, FSE v2, notas v4, etc.).

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
1. Captar actividad económica del cliente en el formulario; identificación obligatoria sobre el monto legal (pendiente del contador).
2. Probar el constructor contra las 412.743 líneas históricas (BD de desarrollo, solo lectura) y medir cuántas ventas pasan el esquema.
3. Constructores de Nota de Crédito/Débito (05/06), Exportación, Sujeto Excluido.
4. Firma (firmador oficial por cliente), transmisión, reintentos y eventos.

## Pendiente de planear (después de terminar las fases de facturación)

**Módulo de Contabilidad** (idea de Luis, 2026-11-20). Hoy no existe contabilidad real: el módulo "Contabilidad" solo trae Bancos. Existe IVA por venta, `tax_rates`, libros de IVA (desde ventas, no desde DTE aceptados), CxC/CxP operativos. Falta: catálogo de cuentas, partidas (manuales y automáticas), Diario/Mayor, balanza y estados financieros, cierres, activos fijos, datos para F07/F14, asientos de retenciones/percepciones, conciliación bancaria, y valuación de inventario (PEPS o costo promedio) para el costo de ventas.

Criterios ya acordados para planearlo:
- Va **después** de la facturación: depende del DTE aceptado con sello (la contabilidad e IVA van por fecha de generación, Normativa 10.2), de impuestos con vigencia (ya están) y de la valuación de inventario.
- Diseño **por eventos** (venta confirmada, DTE aceptado, compra recibida, abono): sin leer tablas ajenas, apagable sin romper el núcleo, igual que los demás módulos.
- Esfuerzo comparable a la Fase 2 completa; **requiere definir con un contador** qué es "necesario" (catálogo base, política de cierres, NIIF para PYMES) antes de diseñar.
- Orden sugerido por valor/riesgo: (1) libros de IVA desde DTE aceptados, (2) datos F07 y F14, (3) retenciones, (4) partidas completas, Diario/Mayor y cierres.

Estado: solo idea; no hay diseño ni código. Tarea de planeación pendiente.

## Pendiente de terceros
- Confirmar con el contador el **monto legal** a partir del cual el receptor debe identificarse.
- Códigos reales de establecimiento/punto de venta, distrito→municipio, credenciales y certificado del ambiente de pruebas, Docker en el servidor.

## Cómo retomar en otra sesión

**Estado:** rama `fase1-dte-v2`, todo comiteado y subido (último commit: ver `git log`). Punto de retorno previo a todo el trabajo: etiqueta `pre-fase1-dte`. El servidor de evaluación sigue **esta rama**, no `master`.

**Arranque rápido (PC de desarrollo)**
1. Levantar MySQL de Laragon (suele estar apagado). Si hace falta por línea de comandos: `mysqld.exe --defaults-file=C:\laragonOld\bin\mysql\mysql-8.4.3-winx64\my.ini`. La app usa `DB_HOST=127.0.0.2`.
2. `php artisan test` → deben pasar los 84 tests (usan SQLite en memoria; no tocan la BD real).
3. La BD de desarrollo **no** tiene aplicadas las migraciones de la Fase 1: todos sus módulos están en `enabled=0` y las migraciones del paquete DTE solo se cargan con el módulo activo. El servidor sí las tiene. Para probar con datos completos, usar el servidor o activar el módulo `dte-sv-mh` en una copia de la BD.
4. Respaldos locales: `storage/app/backups/` (ignorado por git; contiene datos reales de cliente).

**Servidor (192.168.16.191)**: `ssh -i ~/.ssh/id_ed25519_stockia soportecorp@192.168.16.191` desde Git Bash. Desplegar: subir la rama y ejecutar allí `cd /var/www/stockia && bash deploy/server/update.sh` (respaldo + pull + composer + build + migrate + optimize). Respaldos en `~/backups/`. Volver a `master`: ver `FASE1_AVANCE.md`.

**Archivos que existen solo en esta PC (no están en git)**
- `ExportPlugins/DTE v2/`: documentos oficiales (PDF, Excel, zips del firmador y de los esquemas), 264 MB. Respaldar aparte si se quiere conservar.
- `ExportPlugins/DTE v2/ocr_texto/`: texto OCR del Manual Técnico y de la Normativa, y `ocr.ps1` (OCR con Windows, sin instalar nada: `powershell.exe -File ocr.ps1 -Pdf <pdf> -Out <txt>`). El OCR tiene errores de caracteres; no citarlo como texto oficial sin cotejar el PDF. Los Anexos II–V de la Normativa (estructura y validaciones por campo) están ahí pero **no se han leído completos**; en el Anexo IV están las reglas de cálculo y catálogo que Hacienda valida además del esquema.

**Tropiezos conocidos del entorno**
- En el shell Bash de la herramienta, varios heredocs en una misma llamada fallan; escribir archivos con la herramienta de escritura y ejecutar scripts de Python desde archivo.
- Al parchear PHP desde Python, las barras invertidas de los espacios de nombres (como en `App\Support`) se interpretan como escapes: duplicarlas, usar cadenas crudas o escribir los archivos con la herramienta de escritura.
- `.gitattributes` fuerza LF: cualquier archivo cuya huella se compare (esquemas) debe normalizarse a LF.
- Los `Carbon::parse('2031-01-01')` sin zona son UTC; las reglas fiscales usan hora de El Salvador (`config('dte.timezone')`).

**Decisiones tomadas (no reabrir sin motivo)**
- Un firmador oficial **por cliente** (el certificado y la llave quedan en su infraestructura).
- Identificadores: guardados con guiones, derivados sin guiones para el DTE; sin columna duplicada.
- Numeración centralizada o por establecimiento, nunca por punto de venta ni por tipo.
- El modo real no emite con códigos de establecimiento/punto de venta sin confirmar.

**Preguntas abiertas para terceros**
1. Contador: monto legal a partir del cual el receptor debe identificarse; fecha límite real de migración de cada cliente (la del 1-dic-2026 es de fuentes no oficiales); tasas de `tax_rates`; si los tiquetes siguen siendo válidos (Normativa regla 15, desde 1-ene-2025).
2. Hacienda/portal de emisores: códigos reales de establecimiento y punto de venta; jerarquía distrito→municipio; credenciales y certificado del ambiente de pruebas.
3. Sistemas: instalar Docker en el servidor (necesita sudo) para el firmador oficial; el servicio de firma escucha en el puerto 8113.
