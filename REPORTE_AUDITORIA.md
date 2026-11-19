# REPORTE DE AUDITORÍA — Stockia vs. checklist DTE 2.0 (El Salvador)

Fecha: 2026-11-19 · Alcance: lectura de código, sin modificaciones · Base: `auditoria-modulos-sv.md`

## 0. Alcance y límites de esta auditoría

**Leído / verificado:**
- Todo el código del módulo DTE: `packages/dte-sv-mh/`, `app/Services/Dte/DteCalculationService.php`, la librería `ExportPlugins/sv-dte-laravel12/`, `CreateSale`, `PTVPosController` (puntos de llamada), migraciones, rutas, tests y `.env.example`.
- Contraste mecánico contra los **17 JSON Schemas oficiales** (`ExportPlugins/DTE v2/svfe-json-schemas (2).zip`): versión, `tipoDte`, patrón de `numeroControl`, campos de establecimiento del emisor y `direccion.required`. **Confirmé las afirmaciones del MD** (p. ej. `distrito` requerido; NC v4/ND v4 sin `codEstable`/`codPuntoVenta`; DCL v2 con `extension`).
- Contraste contra el firmador oficial (`svfe-api-firmador.zip`, `FirmarDocumentoFilter`): recibe `nit`, `activo`, `passwordPri`, `dteJson`.

**No verificado:**
- El *Manual Técnico de Integración v2* y la *Normativa v2.0* son PDFs escaneados (`pdftotext` devuelve 62 bytes). Por eso **no pude contrastar** contra ellos el contrato HTTP de autenticación, recepción, contingencia e invalidación (URLs, cuerpo, encabezados). Esos ítems van como ❓/🟡 con la sospecha indicada. Hace falta OCR para cerrarlos.
- No ejecuté la aplicación ni los tests; no toqué la base de datos. Nada de lo marcado ✅ está probado en ejecución, solo leído.
- Fechas límite, plazos y tasas: **validar con contador/DGII**, como indica el MD.

---

## 1. Resumen ejecutivo

**Stack:** Laravel 12 / PHP 8.2+ (corre en 8.3), MySQL, Sanctum, DomPDF, Tailwind/Vite. Modular por paquetes Composer locales (`packages/accounting|dte-sv-mh|inventory|ptv-pos|sales|sales-quotes`) más la librería externa `tuempresa/sv-dte`. Multiempresa con `CompanyScope`, multibodega, API REST v1 (Sanctum). Módulos activables con `Module::isEnabled()`.

**Veredicto general:** Stockia es un **POS/ERP comercial sólido** (ventas, compras, inventario por bodega, CxC/CxP, cotizaciones, pedidos, comisiones, garantías, caja) con una **capa DTE que es un prototipo en esquema v1.x con simulador**. No está en condiciones de emitir DTE válidos ni en v1.2 ni en v2.0, y **no emitiría uno aceptado por el ambiente de pruebas de Hacienda** sin rehacer generación, validación, firma y transmisión.

| Módulo | Cobertura estimada | Comentario |
|---|---|---|
| 1. Núcleo / config. fiscal | ~40 % | Datos fiscales y catálogos cargados; falta distrito, códigos M/B/S/P, vigencia de impuestos, certificado seguro, ambientes, bitácora |
| 2. Terceros | ~50 % | Clientes con tipo/nº documento y validación básica; sin distrito, sin agente de retención/percepción, sin sujeto excluido/exportación |
| 3. Ventas | ~65 % | POS, cotizaciones, pedidos, crédito, ajustes; descuento solo por ítem y a 0, sin NC/Retorno, sin orden de facturación desacoplada |
| 4. Inventario | ~55 % | Bodegas, movimientos, traslados, lotes, estantes; sin valuación/kardex valorizado, sin Remisión DTE, sin conteos |
| 5. Facturación DTE | **~10–15 %** | Estructura de tablas y flujo simulado; JSON no cumple ningún esquema vigente |
| 6. Compras | ~45 % | Compras con IVA y crédito; sin OC, sin importación de JSON de DTE recibido, sin DUCA/sujeto excluido |
| 7. CxC / CxP / Tesorería | ~60 % | Abonos y saldos; bancos y movimientos; sin conciliación, anticipos ni arqueo fiscal |
| 8. Contabilidad general | **~5 %** | Solo módulo "Bancos". No hay catálogo de cuentas, partidas, libros ni cierres |
| 9. Reportes fiscales | ~35 % | Libros IVA con CSV; basados en fecha de venta, no en DTE sellado; sin F07/F14 ni formato oficial |

**Migración DTE 2.0: NO INICIADA** (ver §4). Tampoco cumple v1.2 en forma.

---

## 2. Tablas por módulo

Leyenda: ✅ implementado · 🟡 parcial · ❌ falta · ❓ no determinable

### Módulo 1 — Núcleo / Configuración fiscal

| Ítem | Estado | Evidencia | Observaciones |
|---|---|---|---|
| NIT, NRC, nombre comercial, razón social | ✅ | `database/migrations/2026_02_18_060000_prepare_dte_core_schema.php` (cols en `companies`), `DteEmissionService::buildPayload` | Sin validación de formato en `companies` |
| Actividad económica (catálogo oficial) | 🟡 | `companies.cod_actividad`, tabla `dte_cat_actividades_economicas` (774 filas según `MIGRATION_STATUS.md` §5) | Falta confirmar que la UI use el catálogo y no texto libre |
| Establecimientos y PV con código M/B/S/P + 3 dígitos | ❌ | `dte_settings.establecimiento`/`punto_venta` = `string(4)` default `'0001'` | Es **un solo** par por empresa; formato incompatible con `^…(M|B|S|P)[0-9]{3}P[0-9]{3}…`. No existe tabla de establecimientos/PV |
| Numeración centralizada vs. por establecimiento, reinicio anual | ❌ | `DteCorrelativeService::next` | Correlativo por (empresa, **tipo_dte**, estab, **PV**): lo contrario de lo que exige el MD §2.3. Sin reinicio por ejercicio |
| Dirección con departamento, municipio y distrito | ❌ | `companies.departamento(2)`, `municipio(4)`; `normalizeMunicipioCode()` | No hay campo distrito. Se *recorta* el municipio a 2 caracteres, lo que corrompe el dato |
| Catálogos oficiales versionados y actualizables | 🟡 | Tablas `dte_cat_*`, `DteCatalogSeeder`, `ExportPlugins/DTE_catalogos_confirmados/` (CAT-001…032) | Catálogos cargados, pero **sin versión ni fecha de vigencia** y sin CAT-033. Hay 2 fuentes JSON paralelas (`DTE_catalogos_confirmados` y `sv-dte-laravel12/.../json`) |
| Impuestos con vigencia temporal | ❌ | `DteCalculationService`: `IVA_RATE = 0.13` y `RET_IVA_RATE = 0.01` como constantes | Sin tabla de impuestos ni vigencia. `dte_tributos` (CAT-015) existe pero el cálculo no la usa |
| Certificado de firma y credenciales seguros | ❌ | `companies.certificado_firma` (string, `fillable`, sin cast `encrypted`); `dte_settings.api_password` en texto plano | Ver §3 crítica C5 |
| Ambientes pruebas/producción separados | 🟡 | `dte_settings.ambiente` (`00`/`01`), `integration_mode` | Un solo registro por empresa, se alterna con un campo; sin separación de credenciales/tokens por ambiente (el token sí se filtra por `ambiente` en `dte_tokens`) |
| Usuarios, roles, permisos, bitácora | 🟡 | `company_user.role`, `company_user_permissions`, Gates en `AppServiceProvider:81` | Roles y permisos ✅. **Bitácora de auditoría ❌** (sin activity log) |

### Módulo 2 — Terceros

| Ítem | Estado | Evidencia | Observaciones |
|---|---|---|---|
| Tipos (consumidor, contribuyente, gran contrib., excluido, exportación) | 🟡 | `customers.es_contribuyente`, `tipo_documento` | No hay clasificación gran contribuyente / sujeto excluido / exportación (país, `tipoPersona`, etc.) |
| NIT/DUI, NRC, giro; tipo y nº de documento | 🟡 | `customers` (migración `…055900`); `ValidadorDocumentoSalvador` | Falta giro/actividad del receptor (los CCF lo requieren) |
| Dirección con distrito | ❌ | `customers.departamento(2)`, `municipio(4)` | Sin distrito |
| Agente de retención/percepción | ❌ | `DteCalculationService`: opción manual `aplica_retencion_iva` | No es atributo del tercero |
| Validación de NIT/DUI/NRC y correo | 🟡 | `ValidadorDocumentoSalvador` | DUI: solo regex, **sin dígito verificador**. NIT: solo 14 dígitos. NRC: sin validar. Correo: sin evidencia en validadores DTE |

### Módulo 3 — Ventas

| Ítem | Estado | Evidencia | Observaciones |
|---|---|---|---|
| Cotizaciones | ✅ | `packages/sales-quotes` (`sales_quotes`, estados) | |
| Pedidos / apartados | ✅ | `customer_orders` (`MODULOS.md` §4) | |
| POS | ✅ | `packages/ptv-pos` (caja, turnos, `pos_cash_movements`) | |
| Venta a crédito | ✅ | `CreateSale` (`dias_credito`, `fecha_vencimiento`, `saldo_pendiente`), `sale_payments` | |
| Listas de precios y descuentos | 🟡 | `sale_items.descuento`; `DteCalculationService` acepta `discount` | `CreateSale::buildLineItems` fija `'discount' => 0`. Sin listas de precios (decisión previa en `MODULOS.md` §4bis). Sin descuento global |
| Venta por cuenta de terceros | ❌ | — | |
| Devoluciones con referencia (NC / Retorno / invalidación) | 🟡 | `sale_adjustments`, `SaleController` (`admin-adjustment`) | Ajuste **administrativo interno**. No genera Nota de Crédito ni Evento de Retorno; `DteEmissionService::invalidate` existe pero **no tiene ruta ni UI** |
| Venta genera orden de facturación desacoplada | ❌ | `CreateSale` llama `emitForSale()` de forma síncrona (línea ~204) | Acoplado y en línea |

### Módulo 4 — Inventario

| Ítem | Estado | Evidencia | Observaciones |
|---|---|---|---|
| Productos y servicios, unidad oficial | ✅ | `products.tipo_item`, `uni_medida` (CAT-014, 40 filas) | |
| Lotes/series, variantes, almacenes, ubicaciones | 🟡 | `product_batches`, `product_locations`, `warehouses`, `product_warehouse` | Lotes y estantes ✅. **Series y variantes ❌** |
| Movimientos: entradas, salidas, ajustes, traslados | ✅ | `inventory_movements`, `stock_transfers` (con estado en tránsito) | |
| Inventario perpetuo con valuación (PEPS/promedio) | ❌ | `grep` sin resultados; solo `purchase_items.costo_unitario` | Se actualiza "costo" en compras pero no hay método de valuación |
| Kardex y existencias valuadas | 🟡 | `/inventory-movements` | Historial de movimientos de **cantidad**, sin valorización |
| Traslados con Nota de Remisión DTE v4 | ❌ | `StockTransfer` | Traslado interno sin DTE |
| Envases retornables ↔ Evento de Retorno | ❌ | — | |
| Conteos físicos y ajustes justificados | 🟡 | `products/{product}/adjust`, `inventory_movements.reason` | Ajuste justificado ✅; conteo físico ❌ |

### Módulo 5 — Facturación electrónica (DTE)

| Ítem | Estado | Evidencia | Observaciones |
|---|---|---|---|
| JSON conforme al esquema de cada tipo + validación previa contra JSON Schema | ❌ | `FacturaBuilder::make` (`"version" => 1`), fallback en `DteEmissionService::buildPayload` | Sin ningún JSON Schema en el repo ni validación. Ver §4 |
| Tipos soportados | 🟡 | `CreateSale::resolveTipoDte` solo devuelve `'01'`; POS permite `'03'` (`pos.blade.php:105`) | Solo Factura y CCF alcanzables, ambos con estructura v1-like. Sin NC/ND/Remisión/Retención/Exportación/Sujeto Excluido/etc. |
| Código de generación UUID v4 mayúsculas | ✅ | `Helpers/CodigoGeneracion::generar()` → `strtoupper(Str::uuid())` | `Str::uuid()` de este vendor genera v4 (`Uuid::uuid4()`, verificado en `vendor/laravel/framework/.../Str.php:1900`). Solo falta validar el patrón en mayúsculas contra el esquema |
| Número de control | ❌ | `DteCorrelativeService` → `DTE-01-0001-0001-000000000000001` (36 car., 4 segmentos) | Esperado `DTE-01-M001P001-000000000000001` (31 car.). **Falla el patrón de los 17 esquemas** |
| Firma electrónica | ❌ | `RealSigner::sign` | Envía `{certificate, payload}` a `signer_url`. El firmador oficial espera `{nit, activo, passwordPri, dteJson}`. No es compatible. En simulación `FakeSigner` solo hace SHA-256 |
| Transmisión a Hacienda | ❓/❌ | `RealSendService::send` | Hace `POST send_url` con el array firmado. Sospecho que no coincide con el contrato de recepción (documento JWS + `idEnvio`, `version`, `ambiente`, `tipoDte`, `codigoGeneracion`). **No verificable sin OCR del Manual Técnico**. Sin transmisión por lotes |
| Autenticación | ❓ | `RealAuthService` | `Http::post(json)` con `{user,password,ambiente}`; el contrato oficial suele ser `x-www-form-urlencoded` (user/pwd). Verificar |
| Sello de recepción y documento firmado, inalterable, por período | 🟡 | `dtes.json_firmado`, `sello_recepcion`, `json_original` | Se guarda, pero en columnas JSON **mutables** (`update`), sin hash/bloqueo/orden por período, y el firmado real sería un JWS string, no JSON. Sin política de conservación/respaldo |
| Representación gráfica (PDF) con QR y correo | 🟡 | `SaleController::invoicePdf`, vista `sales.pdf-dte` | PDF existe. **QR: sin evidencia** (`grep qrcode` vacío). **Envío por correo: ❌** (sin `Mail::`/Mailable) |
| Eventos: contingencia v4 | 🟡 | `dte_contingencias`, `dte_contingencia_dtes`, `emitForSale` rama `contingencia`, `dte:reenviar-contingencia` | Contingencia **simulada** ("Falla internet simulada"), sin evento v4 enviado, sin 24 h/72 h, sin responsable, `resendContingency` fuerza `'simulacion'` |
| Eventos: invalidación v3 | 🟡 | `DteEmissionService::invalidate`, `RealInvalidationService`, `dte_invalidaciones` | Cuerpo propio (no el esquema v3, sin firma, sin plazos por tipo). Marca `INVALIDADO` aunque falle el envío (la excepción se propaga antes, pero en simulación siempre pasa). **Sin ruta ni UI** |
| Eventos: retorno v1 / operaciones especiales v1 | ❌ | — | |
| Cola de reintentos, manejo de rechazos, estados | 🟡 | `dte_send_attempts`, estados `PENDIENTE/ACEPTADO/RECHAZADO/CONTINGENCIA/INVALIDADO` | Hay registro de intentos, pero **sin cola** (`ShouldQueue`/`dispatch` ausentes), sin reintento automático, sin scheduler (`routes/console.php` solo comando manual). Un `RECHAZADO` no se puede reintentar: `emitForSale` consumiría otro correlativo |
| Idempotencia | 🟡 | `emitForSale` reutiliza DTE si estado ∈ PENDIENTE/ACEPTADO/CONTINGENCIA | No cubre carrera entre dos llamadas simultáneas ni el caso `RECHAZADO`/excepción tras consumir correlativo |
| Soporte de dos versiones de esquema | ❌ | `"version" => 1` literal en 2 sitios | Sin selector por fecha ni feature flag |
| Redondeo y holgura (§2.6) | 🟡 | `DteCalculationService::round2`, `EPSILON = 0.02` | Redondea a 2 decimales **por línea**, sin 8 decimales en cuerpo; `quantity` se castea a **`(int)`** (no admite cantidades fraccionarias); el EPSILON es 0.02 (norma ±0.01); el control es auto-referencial (compara la suma con sí misma) |

### Módulo 6 — Compras

| Ítem | Estado | Evidencia | Observaciones |
|---|---|---|---|
| Órdenes de compra y recepción ligada a inventario | 🟡 | `purchases`, `purchase_items` actualizan stock y costo | Compra directa; **sin orden de compra** (`MODULOS.md` §4bis lo descartó) |
| Locales, importación (DUCA), sujeto excluido | ❌ | — | Solo compra local |
| Importar JSON de DTE recibido (1.2 y 2.0) | ❌ | — | |
| Crédito fiscal desde sello | 🟡 | `purchases.iva` (migración `…080100`, `…030000`) | IVA derivado de `afecto_iva` del producto; no depende de DTE sellado |
| Retenciones aplicables | ❌ | — | |

### Módulo 7 — CxC / CxP / Tesorería

| Ítem | Estado | Evidencia | Observaciones |
|---|---|---|---|
| Cobros y pagos parciales/totales, saldos | ✅ | `sale_payments`, `purchase_payments`, `AccountsReceivableController`, `AccountsPayableController` | |
| Antigüedad de saldos | 🟡 | `fecha_vencimiento` | Sin reporte de antigüedad por rangos confirmado |
| Anticipos | ❌ | — | |
| Retenciones/percepciones aplicadas al cobro | ❌ | — | |
| Conciliación bancaria y arqueos | 🟡 | `banks`, `bank_accounts`, `bank_movements`; `pos_sessions` (cierre de turno) | Libro de movimientos ✅; **conciliación ❌**; arqueo de caja ✅ en POS |
| Campos de pago del DTE (forma de pago, plazo CAT-018, período) | ❌ | `buildPayload`: `resumen` sin `condicionOperacion`, `pagos`, `plazo` | `sales.payment_method` es `cash`/`credit`, sin mapear a CAT-017/CAT-018 |

### Módulo 8 — Contabilidad general

| Ítem | Estado | Evidencia | Observaciones |
|---|---|---|---|
| Catálogo de cuentas, partidas automáticas, cierres, libros, EEFF, activos fijos | ❌ | `packages/accounting` contiene solo Bancos | El nombre del módulo induce a error (`MODULOS.md` lo aclara: "Bancos") |
| Uso de fecha de generación del DTE | ❌ | `DteVatBooksController` usa `created_at` de la venta | Ver Módulo 9 |

### Módulo 9 — Reportes y cumplimiento fiscal

| Ítem | Estado | Evidencia | Observaciones |
|---|---|---|---|
| Libros de IVA (Compras, Contribuyentes, Consumidor) | 🟡 | `DteVatBooksController`, ruta `dte.books.*` | Filtra por `s.status='completed'` y `tipo_dte`, **no** por DTE aceptado/sello/no invalidado, ni por fecha de generación del DTE |
| F07 / F14 / anexos de retenciones | ❌ | — | |
| CSV en formato oficial | 🟡 | `dte.books.export` | CSV propio, sin validar contra layout oficial del anexo |
| Conversión JSON DTE → tabular | ❌ | — | |
| Conservación y orden por período tributario | ❌ | — | |

---

## 3. Brechas críticas (bloquean operar legalmente)

Ordenadas por prioridad.

| # | Brecha | Evidencia | Impacto |
|---|---|---|---|
| **C1** | **Los DTE se generan con `identificacion.version = 1`** y estructura v1.x; solo `FacturaBuilder` existe | `FacturaBuilder.php`, `DteEmissionService::buildPayload` | Ni v1.2 ni v2.0. Factura exige v2, CCF v4, etc. |
| **C2** | **Número de control inválido** (`DTE-01-0001-0001-…`) | `DteCorrelativeService.php` | Falla el patrón de todos los esquemas; Hacienda rechaza |
| **C3** | **Sin validación contra JSON Schema oficial** (solo `DummyValidator`: 3 chequeos; `SimuladorMhValidator`: solo suma de gravadas) | `sv-dte-laravel12/.../DummyValidator.php`, `SimuladorMhValidator.php` | Se firmaría y enviaría basura sin detección previa |
| **C4** | **Firma y transmisión no son compatibles con el firmador/recepción oficiales** (y no verificadas contra el Manual Técnico) | `RealSigner`, `RealSendService`, `RealAuthService` | Imposible obtener Sello de Recepción real |
| **C5** | **Secretos sin protección**: `api_password`, `certificado_firma` y la contraseña de la llave en texto plano en BD; contraseña por defecto `'CERTIFICADO-DUMMY'`/`'TOKEN-DUMMY'` sembrada | `DteSetting` (sin cast `encrypted`), `Company::$fillable`, `resolveSettings()` | Riesgo de seguridad y cumplimiento. También **dummy activado por defecto** en cada empresa nueva |
| **C6** | **`totalLetras` fijo: `'TOTAL EN MODO DUMMY'`** | `DteEmissionService.php` (bloque `resumen`) | Todo DTE llevaría un monto en letras falso |
| **C7** | **Datos del DTE incompletos/obsoletos**: sin `distrito`, sin `codEstable`/`codPuntoVenta`, `tipoEstablecimiento` en el emisor, `ivaRete1`/`reteRenta` | `buildPayload`, `companies`/`customers` | Rechazo por esquema (§4) |
| **C8** | **Modo dummy/simulación reportado como "ACEPTADO"** con sello falso almacenado en `dtes.sello_recepcion` | `FakeSendService`, `resolveSettings` (default `simulacion`), `static_sello` | Riesgo de confundir un DTE simulado con uno válido. No hay marca visible que lo distinga en la representación gráfica |
| **C9** | **Sin Evento de Contingencia real, sin NC/ND/Invalidación operables** | `emitForSale` rama `contingencia`; `invalidate` sin ruta | No hay forma legal de corregir ni de operar sin conexión |
| **C10** | **Conservación inalterable y por período no garantizada** | `Dte` modelo con `update()`, columnas JSON | Incumple Normativa §12 (según el MD) |
| **C11** | **Sin suite de pruebas** contra el ambiente de pruebas de Hacienda ni tests del módulo DTE | `tests/` solo `CompanyScopeTest`, `ExampleTest`, Auth | No se pueden alcanzar las 90/75/… pruebas requeridas para autorización |
| **C12** | **Libros IVA basados en ventas, no en DTE aceptados** | `DteVatBooksController` | Declaración inconsistente con lo que Hacienda tiene |

---

## 4. Estado de migración a DTE 2.0

**Veredicto: ❌ NO INICIADO.** No hay emisión conforme ni siquiera a v1.2.

**Versión de esquema usada hoy:** `1` fija, en dos lugares (`FacturaBuilder` y el fallback de `buildPayload`). No hay selector por fecha ni feature flag. Fecha límite reportada (1-dic-2026, **confianza media, confirmar con Hacienda**) está a **~12 días**.

Contraste ítem a ítem (§2.2–2.4 del MD, confirmado contra los schemas):

| Requisito 2.0 | Estado | Evidencia |
|---|---|---|
| `identificacion.version` por tipo (F=2, CCF=4, …) y `tipoDte` | ❌ | `version = 1` |
| Esquemas oficiales versionados en el repo y validación previa | ❌ | No hay `*.json` de schemas en el proyecto (solo catálogos) |
| Código de generación UUID v4 | ✅ | `Str::uuid()` → v4, en mayúsculas |
| Número de control `…-(M\|B\|S\|P)NNNPNNN-15díg` | ❌ | Genera `0001-0001` |
| Consecutivo centralizado/por establecimiento + reinicio anual | ❌ | Por tipo+estab+PV, sin reinicio |
| `direccion.distrito` en emisor y receptor | ❌ | No existe columna; municipio se recorta a 2 chars |
| Emisor: `codEstable`/`codPuntoVenta`; sin `tipoEstablecimiento`, `codEstableMH`, `codPuntoVentaMH` | ❌ | Emisor lleva `tipoEstablecimiento`; faltan los demás |
| NC v4 / ND v4 sin `codEstable`/`codPuntoVenta` | ❌ | No se generan NC/ND |
| `ivaRete`/`ivaPerci` (ya no `…1`) | ❌ | `ivaRete1` en `resumen` |
| `reteRenta` fuera de F/CCF/NC/ND | ❌ | Se envía `reteRenta` en el resumen |
| Sin objeto `extension` (salvo DCL v2) | ❌ | `FacturaBuilder` emite `"extension" => null` |
| NC/ND: receptor con `tipoDocumento` y `numDocumento`; CCF con `nit` | ❌ | — |
| Campo `fusion` donde aplique | ❌ | — |
| Exportación v3: `tipoRegimen`/`regimen` | ❌ | No hay Exportación |
| Eventos v3/v4/v1 conforme a su esquema | ❌ | Invalidación con cuerpo propio; contingencia simulada |
| Redondeo 8 decimales cuerpo / 2 resumen, holgura ±0.01 | 🟡 | `round2` por línea, EPSILON 0.02 |
| Entrega de JSON firmado + representación gráfica coincidentes, con QR y sello | 🟡 | PDF sí, QR no evidenciado |
| Suite de pruebas contra ambiente de Hacienda | ❌ | — |

**Dato útil:** los modelos de datos de Stockia ya llevan `tipo_documento`/`numero_documento`, tipo de item y unidad de medida oficial, y los catálogos CAT-009/012/013/014/015/019/022/024 están cargados. Eso reduce el esfuerzo de datos, pero no el de generación.

**Hallazgo sobre CAT-013 (actualizado en Fase 0):** `dte_municipios.codigo` guarda departamento+municipio (4 caracteres) y el builder toma los 2 últimos, lo que es correcto para CAT-013. **No hay mezcla de municipio y distrito**; lo que falta es el distrito (CAT-008, ahora vigente) y su jerarquía. Ver `FASE0_HALLAZGOS.md` §4.
---

## 5. Evaluación de arquitectura

| Pregunta | Evaluación | Evidencia |
|---|---|---|
| Límites claros por módulo | ✅ **Bueno.** 6 paquetes Composer con provider, rutas, migraciones y vistas propias, gateados por `Module::isEnabled()` | `packages/*`, `MODULOS.md` §5–6 |
| Dependencias circulares | 🟡 Sí, **acoplamiento Core ⇄ paquete**: `app/Actions/Sales/CreateSale.php` importa `Stockia\DteSvMh\Services\DteEmissionService`; el paquete importa `App\Models\Sale`, `Company`, `Customer`; `PTVPosController` también llama a DTE. Es referencia circular a nivel de namespaces (Core→DTE→Core) | `CreateSale:204`, `DteEmissionService` imports |
| Comunicación por eventos | ❌ No hay eventos de dominio (`venta_confirmada`, `dte_aceptado`); llamada directa síncrona y acceso directo a tablas ajenas con `DB::table('dte_*')` desde el servicio | `grep Event::/event(` solo Auth; `DteEmissionService` usa `DB::table` |
| Facturación puede operar sola (API propia) | ❌ Depende de `Sale`/`SaleItem`/`Company`/`Customer` de Core; no acepta un documento arbitrario | `emitForSale(Sale $sale)` |
| Contabilidad alimentada por eventos | ❌ No aplica (no existe contabilidad) | — |
| Activar/desactivar módulo sin romper otros | 🟡 Bien en UI y rutas (`ModuleGateServiceProvider`). Pero `CreateSale` depende de `Module::isEnabled('dte-sv-mh')` y de `class_exists(\TuEmpresa\SvDte…)`. Con DTE apagado, `sales.tipo_dte` queda sin efecto pero los libros IVA (dentro del paquete) siguen basándose en ventas | `CreateSale`, `ModulesSeeder` |
| Multiempresa / multisucursal | ✅ Multiempresa con `CompanyScope` global, multibodega. 🟡 **Multi-establecimiento fiscal ❌** (un par estab/PV por empresa) | `app/Scopes/CompanyScope.php`, `dte_settings` unique por `company_id` |
| Esquemas y catálogos DTE versionados | ❌ No hay carpeta de schemas ni capa de versionado; el builder es una sola clase con estructura fija. Catálogos sin versión | `ExportPlugins/sv-dte-laravel12` |

**Observaciones de arquitectura propias de la librería DTE:**
- La lógica fiscal está repartida en 3 lugares: `tuempresa/sv-dte` (en `ExportPlugins/`, instalada como *path repository*), `packages/dte-sv-mh` y `app/Services/Dte/DteCalculationService`. El servicio usa `class_exists(\TuEmpresa\…)` para decidir si usa el builder; si falta, silenciosamente cae a otro generador. **Eso permite emitir con dos estructuras distintas según el entorno.**
- El patrón `Real*/Fake*` con `DteGatewayFactory` es una **buena base** para cambiar a servicios reales y conviene conservarlo.
- `resolveSettings()` crea configuración *por defecto con dummy activado* en el primer uso: peligroso para un despliegue a producción.
- Se **simula dentro del flujo de producción** (modo `simulacion` por defecto en `config/dte.php`): una instalación mal configurada emite "ACEPTADO" falso.
- Dinero: BD en `decimal(12,2)` ✅, pero el cálculo usa `float` PHP + `round()`. Para el redondeo de Hacienda conviene `bcmath`/enteros.
- Seguridad: la ruta DTE exige `role:Admin,SuperAdmin` + `can:dte-*` ✅.

---

## 6. Plan de acción

Esfuerzo: **S** (≤ 2 días) · **M** (3–8 días) · **L** (> 8 días). Estimaciones orientativas para una persona.

### Fase 0 — Decisiones y verificación (previo a codificar)

| # | Tarea | Esf. | Dep. |
|---|---|---|---|
| 0.1 | Hacer OCR del Manual Técnico v2 y de la Normativa v2 (secciones 4–6, 8–11, 13 y anexos); cerrar contrato HTTP de auth/recepción/contingencia/invalidación | S | — |
| 0.2 | Confirmar con Hacienda/contador: fecha límite, v1.2 vs 2.0 durante transición, plazo art. 147, comprobante de renta | S | — |
| 0.3 | Decidir qué tipos de DTE necesita realmente el negocio (el MD pide pruebas solo de los usados). Mínimo probable: 01, 03, 05, 07, 14 + eventos invalidación/contingencia | S | 0.2 |
| 0.4 | Definir modelo de ubicación (departamento/municipio/distrito CAT-012/013/008) | S | 0.1 |

### Fase 1 — Núcleo fiscal (bloquea todo)

| # | Tarea | Esf. | Dep. |
|---|---|---|---|
| 1.1 | Tabla `establecimientos` y `puntos_venta` (código M/B/S/P+3 y P+3, tipo CAT-009, distrito) | M | 0.4 |
| 1.2 | Migración de datos: distrito en `companies`, `customers`, sucursales | M | 0.4, 1.1 |
| 1.3 | Numeración: modo centralizado/por establecimiento, 15 dígitos, reinicio por ejercicio, bloqueo transaccional real (`lockForUpdate` sobre fila garantizada) | M | 1.1 |
| 1.4 | Cifrar secretos (`encrypted` cast) para `api_password`, `certificado_firma` y contraseña de llave; eliminar defaults dummy en producción; separar credenciales por ambiente | S | — |
| 1.5 | Catálogos versionados (versión + fecha) y unificar la fuente JSON | S | — |
| 1.6 | Tabla de impuestos con vigencia (IVA, retenciones, percepción, pago a cuenta) | M | — |

### Fase 2 — Facturación DTE v2 (prioridad máxima)

| # | Tarea | Esf. | Dep. |
|---|---|---|---|
| 2.1 | Incorporar los 17 JSON Schemas oficiales al repo (`resources/dte/schemas/{v}`) con `opis/json-schema` o equivalente; validar **antes** de firmar | M | — |
| 2.2 | Capa `DteBuilder` por tipo y versión (interfaz + fábrica por fecha/flag); reescribir Factura v2 y CCF v4 | L | 1.1, 1.2, 2.1 |
| 2.3 | Reescribir cálculo: 8 decimales en cuerpo, 2 en resumen, holgura ±0.01, cantidades decimales, `totalLetras` real, `condicionOperacion`/`pagos`/CAT-017/018 | M | 1.6 |
| 2.4 | Firma real contra el firmador oficial (`nit`, `activo`, `passwordPri`, `dteJson`) y guardar el JWS | M | 0.1, 1.4 |
| 2.5 | Cliente real de autenticación y recepción (según Manual Técnico); manejo de rechazos y observaciones | M | 0.1, 2.4 |
| 2.6 | Cola (`ShouldQueue`) con reintentos, backoff, idempotencia por `codigo_generacion`, consumo de correlativo solo al aceptar o con estrategia definida | M | 2.5 |
| 2.7 | Almacenamiento inalterable por período (sin `update` sobre JSON firmado; hash; export/respaldo) | M | 2.5 |
| 2.8 | Marca visible "SIMULACIÓN / SIN VALIDEZ FISCAL" en toda representación de DTE no real; bloquear modo simulación en producción | S | — |
| 2.9 | Representación gráfica con QR y sello; envío por correo con JSON adjunto | M | 2.5 |
| 2.10 | Evento de Invalidación v3 (UI + ruta + plazos por tipo) | M | 2.5 |
| 2.11 | Evento de Contingencia v4 real (24 h / 72 h, lote, responsable) | L | 2.5 |
| 2.12 | NC v4 / ND v4 y devoluciones desde `sale_adjustments` | L | 2.2 |
| 2.13 | Factura de Sujeto Excluido v2 y Comprobante de Retención v2 (si se usan) | L | 2.2 |
| 2.14 | Evento de Retorno v1 (si se usan envases/reembolsos) | M | 2.5 |

### Fase 3 — Calidad y autorización

| # | Tarea | Esf. | Dep. |
|---|---|---|---|
| 3.1 | Tests unitarios: cálculo, redondeo, número de control, validación de schema por tipo | M | 2.1–2.3 |
| 3.2 | Suite contra ambiente de pruebas de Hacienda (cantidades mínimas del MD §2.8 para los tipos usados) | L | 2.5 |
| 3.3 | Solicitud de ingreso al ambiente de pruebas y autorización como emisor (documentos en `ExportPlugins/DTE v2`) | M | 3.2 |

### Fase 4 — Resto de módulos (después de DTE)

| # | Tarea | Esf. | Dep. |
|---|---|---|---|
| 4.1 | Libros IVA desde DTE aceptados y no invalidados, por fecha de generación; formato oficial | M | 2.5 |
| 4.2 | Importación de JSON de DTE recibidos (compras, v1.2 y 2.0) | M | 2.1 |
| 4.3 | Atributos de agente de retención/percepción y gran contribuyente en `customers` | S | 1.6 |
| 4.4 | Valuación de inventario (costo promedio) y kardex valorizado | L | — |
| 4.5 | Nota de Remisión v4 desde traslados | M | 2.2 |
| 4.6 | Eventos de dominio (`SaleCompleted`, `DteAccepted`), desacople Core↔DTE, orden de facturación | M | 2.6 |
| 4.7 | Bitácora de auditoría | M | — |
| 4.8 | Contabilidad general real (catálogo, partidas automáticas, libros, cierres) | L | 4.6 |
| 4.9 | F07/F14, anexos, conciliación bancaria, anticipos, antigüedad de saldos | L | 4.1 |

**Orden crítico:** 0.1 → 1.1/1.2/1.3 → 2.1 → 2.2/2.3 → 2.4/2.5 → 2.6–2.9 → 3.x. Con la fecha reportada de 1-dic-2026, **cumplirla es improbable** (esfuerzo Fase 1 + 2 mínimo ≈ 6–10 semanas). Conviene confirmar con Hacienda si se puede continuar en v1.2 mientras tanto; hoy tampoco se cumple v1.2 en forma.

---

## 7. Riesgos y preguntas abiertas

**Riesgos**
1. **Plazo**: la fecha límite está a días y la migración no ha iniciado. Si es firme, hay clientes en riesgo de no poder facturar.
2. **Datos reales en el repo de desarrollo** (`project-stockia-dev-data-warning`): la BD de desarrollo tiene datos del cliente Quezalteca; no reutilizarla para un despliegue nuevo, y cuidar que las pruebas contra Hacienda no usen NIT/credenciales reales de producción.
3. **DTE simulados con sello falso en producción** (C8): riesgo de entregar a clientes documentos sin validez creyendo lo contrario.
4. **Secretos en texto plano** (C5) en BD con respaldos y en un repo sincronizado a GitHub (`luislatin1/Stockia`). Revisar historial de git por credenciales (`.env`, `dte_settings`, dumps).
5. **Correlativos huérfanos**: `emitForSale` consume correlativo antes de transmitir; un fallo deja huecos. Definir política con el contador (¿se permiten saltos?).
6. **Dependencia de ruta local** a `ExportPlugins/sv-dte-laravel12` (junction de Windows, ya causó fallos en `MIGRATION_STATUS.md` §1) para una librería de ~100 líneas.
7. **Falsa sensación de cobertura**: el módulo "Contabilidad" es solo Bancos; no vender como contabilidad general.

**Preguntas abiertas**
1. ¿Confirmó Hacienda por canal oficial la fecha del 1-dic-2026 y que se aplica a *Sistema de Transmisión*? ¿Se puede emitir en 1.2 hasta entonces? (el MD lo marca confianza media)
2. ¿Qué tipos de DTE usa cada cliente? Define el alcance real (y el número de pruebas).
3. ¿Cuál es el contrato HTTP exacto de autenticación/recepción/contingencia/invalidación? (Manual Técnico escaneado: pendiente de OCR.)
4. ¿Se desplegará el firmador oficial (Java, Docker) como servicio por cliente o centralizado? (hay `svfe-api-firmador.zip` y un `dte-firmador.zip.opdownload` **incompleto** de 236 MB en `ExportPlugins/DTE v2`: está truncado, hay que volver a descargarlo)
5. ¿Plazo de conservación (art. 147) y dónde se guardarán los DTE (disco, S3, respaldo)?
6. ¿Comprobante de retención de renta (art. 156) es DTE o no? (pendiente §8 del MD)
7. (Resuelta en Fase 0) Municipio y distrito no están mezclados; falta la jerarquía distrito → municipio, ver `FASE0_HALLAZGOS.md` §4.
8. ¿Número de control `M/B/S/P`: cuál es el código que Hacienda asignó a cada establecimiento de cada cliente (CAT-009 y portal de emisores)?
10. ¿Se usa Venta Simplificada / Comprobante de Control Interno (art. 113)? Determina si el Evento de Operaciones Especiales aplica.

---

*Actualización 2026-11-19: ver `FASE0_HALLAZGOS.md` (contrato HTTP oficial confirmado por OCR del Manual Técnico y reglas adicionales de la Normativa).*

*Este documento es un diagnóstico técnico basado en lectura de código y en los documentos oficiales disponibles; no constituye asesoría legal ni tributaria. Los ítems marcados ❓ o con "validar" requieren confirmación con DGII/contador.*
