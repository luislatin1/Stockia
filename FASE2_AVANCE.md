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
- 100 tests pasan (15 del constructor, todos validan contra el esquema oficial).

**Prueba contra el histórico (2026-11-20, BD de desarrollo, solo lectura):** `php artisan dte:probar-historico` (opciones `--limit`, `--company`, `--desde`, `--sin-esquema`). Repite cada venta de `legacy_sales` (tickets no anulados) como Factura 01 con emisor/receptor sintéticos, valida contra el esquema oficial y compara el total con la suma de líneas originales. Resultado: **95.147 ventas / 409.184 líneas, 0 fallos de esquema, 0 descuadres** (344 s). Excluidas: 26 líneas con cantidad o subtotal ≤ 0 y 1 venta sin líneas utilizables. Limitación: prueba ítems y cálculo, no datos reales de emisor/receptor ni CCF. Con el módulo apagado (BD de desarrollo) el comando no se registra; se ejecutó con un script que lo registra a mano.
- Hallazgo: el validador marcaba como inválido 4,35 (múltiplo de 0,01) porque opis comprueba `multipleOf` con `fmod` en coma flotante (7 de cada 2.000 ventas). Corregido en `DteSchemaValidator` comprobando por decimales; sigue rechazando 1,125.
- Formulario de clientes (DTE > Clientes): campos de código y descripción de actividad económica; el CCF se niega con mensaje claro si falta la actividad o la dirección completa del cliente.

**Desplegado y probado en el servidor (2026-11-20, commit 65d877c, migración de `customers` aplicada):** histórico (3.000 ventas) 0 fallos; emisión simulada con rollback de Factura 01 y CCF 03 con la empresa real: sin datos completos el esquema bloquea (faltan `distrito`, `telefono`, `correo` del emisor, como se sabía); completándolos dentro de la transacción ambos salen ACEPTADO (simulación) con total 113,00. Comprobado que el rollback no dejó nada (0 ventas, 0 DTE, 0 clientes). **Para emitir de verdad falta capturar distrito, teléfono y correo de la empresa en Administración Central.**

### Material nuevo en `ExportPlugins/DTE v2/nuevo/` (revisado 2026-11-20)
Contenido: `dte-2.0-referencia-oficial.zip` (manual funcional completo en texto, normativa OCR solo págs. 1-35, catálogos CAT-001..033 v1.1 como **transcripción de terceros**, 15 esquemas, resumen de reglas), `mapa_distrito_municipio_departamento.csv`, `validacion_catalogos.md`. Solo en esta PC (no en git).
- **Esquemas:** los 15 JSON son **idénticos** (sha256) a los que ya versionamos: sin novedad.
- **Verificado contra el catálogo:** CAT-017 `01` = billetes y monedas, CAT-018 `01` = días, CAT-016 (1 contado, 2 crédito, 3 otro), CAT-006 `22` = retención IVA 1 %. Los supuestos de 2.2 eran correctos.
- **Aprovechado:** el CSV trae la relación distrito→municipio que CAT-008 no tiene. Coincide al 100 % con lo cargado (262 distritos, 44 municipios, mismos códigos y nombres). Migración `2026_11_20_110000_fill_distrito_municipio` llena `dte_distritos.municipio_codigo` y desde ahora se valida la tripleta departamento/municipio/distrito también en clientes. Límite: la relación es de terceros (decreto, por nombre); que Hacienda rechace una tripleta incoherente **no está confirmado**.
- **Útil para después:** NC/ND v4 no llevan `codEstable`/`codPuntoVenta` en el emisor; pruebas mínimas de transmisión (Factura 90, CCF 75, NC 50, ND 25); plazos de invalidación y de contingencia (24 h evento, 72 h lote); el Manual Técnico de Integración **sí se recibió** (Fase 0, `ExportPlugins/DTE v2/Manual Técnico para la Integración Tecnológica del Sistema de Transmisión v2.pdf`, con OCR en `ocr_texto/`): el paquete `nuevo/` solo no lo incluía.
- **No aporta:** Normativa págs. 36-137 (Anexos II-IV) siguen sin texto utilizable aparte del OCR que ya teníamos; catálogos no cotejados código por código.

### 2.3 Nota de Crédito (05) y Nota de Débito (06) — constructor hecho 2026-11-20
- `DteV2Builder` ahora arma 05 y 06 (JSON v4): receptor con `tipoDocumento`/`numDocumento`, `documentoRelacionado` obligatorio (hoy solo CCF 03, por código de generación, fecha de emisión del original), `numeroDocumento` por ítem, sin `codEstable`/`codPuntoVenta` ni `otrosDocumentos`, ítems con `ivaPerci`/`totalIva`/`ivaRete`, resumen sin `subTotal` ni `pagos`, `codigoRetencionMH` (`22` si hay retención) y `numPagoElectronico` solo en la ND. 100 tests pasan; ambos validan contra el esquema oficial.
- **Falta (no es el constructor):** el flujo de negocio. `sale_adjustments` existe pero ningún código lo usa; `emitForSale` busca el DTE por venta y devolvería el original. Hay que decidir: tabla/columna que ligue la nota al DTE original (con sello), qué pantalla la dispara (devolución → NC; recargo → ND), efecto en inventario y cuentas por cobrar, y que solo se pueda sobre un DTE ACEPTADO. Las notas sobre Factura 01 no existen (se usa Evento de Retorno/invalidación).
- Supuesto sin verificar: el IVA por ítem de la nota es ventaGravada × 13 % (regla del Anexo IV leída del OCR), y el tributo 20 del resumen es totalGravada × 13 %.

### Devoluciones y motivos: qué dice Hacienda (analizado 2026-11-20, fuente: Manual Funcional v2.0 §VII y §X, esquemas)
- **La Nota de Crédito no tiene campo "motivo"** en el esquema; solo `observaciones` (texto libre). Ajusta por ítem un CCF: devolución de producto, descuento omitido, corrección de precio (ejemplo oficial: una sola NC ajusta varios CCF).
- **Factura (01) no se ajusta con NC.** Devolución total (rescindir) → *Evento de Invalidación* tipo 2, hasta 3 meses desde el sello. Devolución parcial o reembolso → *Evento de Retorno* (fe-eret-v1; 3 meses, 2 años para ciertas actividades). **Cambio de producto → Evento de Retorno + nuevo DTE** por la nueva operación (manual §X).
- **Invalidación (CAT-024):** 1 error en la información, 2 rescindir la operación, 3 otro (incluye cambio de bienes/servicios). Con 1 o 3 hay que emitir primero el DTE que reemplaza y tenerlo con sello. Motivos "justificados" del manual: error en identificación del cliente, error en producto/servicio, **error en la fecha de la operación**, error en precio (solo Factura/FEX), devolución de bienes, rescisión total. CCF: plazo hasta el 10.º día hábil del mes siguiente. Un CCF con NC/ND validada no se invalida sin invalidar antes la NC/ND.
- **No está en la documentación de Hacienda:** *desperfecto* y *garantía*. Son clasificaciones internas (afectan inventario y reclamo al proveedor), no se transmiten. *Cambio de fecha* no es NC: es el motivo "error en la fecha" → invalidar y reemplazar.
- **Hueco en Stockia hoy:** el ajuste administrativo (`SaleController::storeAdminAdjustment`, acciones `cancel_sale`/`void_invoice`) cambia el estado de la venta, mueve inventario y caja, pero **no toca el DTE**: una factura "anulada" sigue ACEPTADA ante Hacienda.

### 2.4 Devoluciones con DTE (construido 2026-11-20)
Se ejecuta desde el ajuste administrativo de ventas (`SaleController::storeAdminAdjustment`, pantalla `sales/admin-adjustment`). Código nuevo en `packages/dte-sv-mh/src/Devolucion/` (`RutaDevolucion` matriz motivo×documento, `PlazoInvalidacion`, `DteDevolucionService`) y `DteEmissionService::emitirNotaCredito`.
- **Acciones:** cancelar, anular factura (como antes) y **"Devolver / ajustar"** (nueva: devolución parcial o ajuste de valor; la venta sigue vigente salvo que se devuelva todo).
- **Motivo obligatorio si la venta tiene DTE:** devolución, garantía/desperfecto, cambio de producto, error de precio, descuento omitido, error de fecha, error de datos del cliente. Se guarda en `sale_adjustments.motivo` (junto con `dte_ruta`, `dte_id`, `replacement_sale_id`). Desperfecto/garantía son internos (no se transmiten); el estado "Desperfecto" manda el producto a no vendible.
- **Rutas:** CCF → Nota de Crédito (devolución/garantía/cambio por ítems devueltos; error de precio/descuento omitido por monto sin IVA por línea) o invalidación tipo 1 con reemplazo (error de fecha/datos, solo anulación completa). Factura → devolución/garantía total = invalidación tipo 2; cambio de producto total = tipo 3 con reemplazo; errores = tipo 1 con reemplazo; devolución parcial = Evento de Retorno (ver 2.5).
- **Reglas:** el DTE original debe estar ACEPTADO (con sello); no se devuelve más de lo que queda (acumulado entre ajustes); invalidación respeta plazo (Factura 3 meses; CCF décimo día hábil del mes siguiente, sin feriados) y exige que el documento de reemplazo ya tenga sello; un CCF con notas vigentes no se invalida; la emisión a Hacienda es el **último paso dentro de la transacción**: si falla o la rechazan, se revierte todo (inventario, caja, estado). Sin esto no se puede anular una venta con DTE aceptado (ya no queda "anulada" localmente y vigente ante Hacienda).
- **Migraciones:** `dtes.dte_origen_id`/`sale_adjustment_id` (la nota apunta a su original; `emitForSale` ignora las notas), `sale_adjustments` motivo/ruta/dte/reemplazo, `sale_adjustment_items.credit_amount`.
- **Límites conocidos:** NC solo sobre CCF; las ventas parcialmente devueltas siguen contando completas en reportes de ventas (no se tocó); si el módulo DTE está apagado, el ajuste no informa a Hacienda; en simulación la invalidación/NC siempre salen aceptadas; el IVA de la NC sigue siendo el supuesto de 2.3 (13 % × gravado, Anexo IV leído del OCR). 118 tests pasan.

### 2.5 Evento de Retorno (construido 2026-11-20)
- `EventoRetornoBuilder` arma el evento tipo 18 (esquema `retorno`, fe-eret-v1) sobre una Factura aceptada; `DteEmissionService::emitirEventoRetorno` lo valida contra el esquema, lo firma/transmite como cualquier DTE y lo guarda en `dtes` con `tipo_dte = 18`, `dte_origen_id` = la factura. Los eventos **no tienen número de control**: la columna (única y obligatoria) se rellena con `E18-<código de generación>`.
- Valores como la Factura (precio con IVA incluido en lo gravado, IVA informativo en `totalIva`); `documento` = receptor de la factura (null si era consumidor final); emisor con `codEstableMH`/`codPuntoVentaMH` = códigos M001/P001 del establecimiento.
- **Ruta en devoluciones:** Factura + (devolución | garantía | cambio de producto) + devolución parcial, **o** cualquier devolución cuando ya hay un retorno vigente → Evento de Retorno. Así se completan devoluciones sucesivas hasta devolver todo sin invalidar (una Factura con retorno vigente **no puede invalidarse**, Manual §VII nota 3). Errores de precio/fecha/datos en una factura con retorno vigente quedan bloqueados hasta poder invalidar el evento.
- **Plazo** (`PlazoRetorno`): 3 meses desde la fecha de generación de la factura; 2 años si el emisor tiene actividad 21001, 21008, 46482, 46484, 46491 o 47721. El manual dice en otro párrafo "3 meses desde el sello"; se usa la fecha de generación (más estricta). El monto acumulado nunca supera lo vendido porque las cantidades devueltas se descuentan entre ajustes.
- El esquema del evento solo admite `motivoContin` cuando el tipo de contingencia es 5; el constructor lo respeta (en contingencia automática se usa tipo 1, motivo null).
- **Pendiente:** invalidar un Evento de Retorno (evento de invalidación sobre tipo 18); Evento de Retorno para FEX y FSE (el constructor solo acepta Factura); ND desde la pantalla. 126 tests pasan.

### 2.6 Nota de Débito desde la pantalla (construido 2026-11-20)
- Pantalla propia `sales/{sale}/debit-note` (botón "Nota de débito" en el detalle de una venta vigente con CCF aceptado). Motivos internos: cargo adicional, intereses por mora, diferencia de precio a favor del vendedor, otro cargo (`DteDevolucionService::MOTIVOS_DEBITO`). Hacienda no define motivos: van en `observaciones`. Cargos libres (descripción + monto sin IVA + gravada/exenta/no sujeta), tipo de ítem servicio y unidad "otra".
- `DteEmissionService::emitirNotaDebito` (06) comparte con la Nota de Crédito el método privado `emitirNotaSobreCcf` (CCF aceptado, receptor, numeración, validación de esquema, firma/transmisión). Queda registrada como ajuste (`sale_adjustments.action_type = debit_note`, `dte_ruta = nota_debito`) y visible en el detalle de la venta.
- Efecto en cuentas por cobrar: venta a **crédito** → el total de la ND (con IVA, menos retención) se suma a `saldo_pendiente`; venta de contado → no se toca el saldo (el cobro se registra aparte). No mueve inventario ni caja ni el total/estado de la venta. Requiere clave de administrador para vendedores, igual que los ajustes. Todo en una transacción: si Hacienda no acepta, se revierte.
- **Supuesto sin verificar en los documentos entregados:** que una ND sirva para aumentar el valor de un CCF (cargos, intereses, diferencias de precio). Es el uso conocido del Código Tributario, pero ni el manual ni los extractos de la normativa lo describen; confirmar con el contador. Solo sobre CCF (no sobre Factura). Invalidar una ND no tiene pantalla aún. 132 tests pasan.

### 2.7 Invalidación de notas y eventos (construido 2026-11-20)
- **Evento de Invalidación real:** `InvalidacionBuilder` arma el evento según el esquema oficial `invalidacion` (v3) para cualquier DTE o evento propio y `DteEmissionService::invalidate` lo valida ANTES de transmitir y lo guarda (`dte_invalidaciones.json_evento`). Esto también aplica a las invalidaciones de Factura/CCF que ya existían. Reglas del manual aplicadas: reemplazo null en rescisión (tipo 2) y siempre en NC y Evento de Retorno; fecha del evento = fecha de generación del documento (CCF/NC/ND/eventos) u hoy (Factura); los eventos no llevan número de control.
- **Responsable del evento:** el esquema exige nombre y documento de quien realiza y solicita la invalidación. Se configura por empresa en DTE > Administración (campos nuevos `responsable_*` en `dte_settings`; se usa la misma persona en ambos roles). **En modo real es obligatorio**; en simulación, si está vacío, se usan datos de prueba rotulados.
- **Sello de simulación:** el esquema pide sello de 40 caracteres y los de prueba son más cortos; en simulación se normalizan a 40 solo para armar el evento (en real, si no tiene 40, se rechaza).
- **Pantalla:** en el detalle de la venta, cada ajuste muestra su documento (NC/ND/Evento de Retorno) con estado y enlace "Invalidar documento" (`sales/{sale}/dte/{dte}/invalidate`). Tipos: rescindir (2), error en la información (1), otro (3). NC y Evento de Retorno: sin reemplazo (si el error era de la nota se emite otra después). ND: con tipo 1 o 3 se exige emitir antes la ND correcta y elegirla como reemplazo (el manual lo pide así). Plazo: décimo día hábil del mes siguiente al sello (sin feriados). Clave de administrador para vendedores.
- **Efectos:** invalidar el Evento de Retorno libera a la factura (vuelve a poder invalidarse); invalidar una NC libera al CCF; invalidar una ND de venta a crédito resta su monto del saldo pendiente. **No se revierten inventario ni caja**; para volver a acreditar un monto sin mover producto se usa "Devolver / ajustar" con motivo de error de precio (NC de valor).
- **Pendiente:** firma real del evento (falta construir la capa real según el Manual Técnico de Integración (ya recibido, ver FASE0_HALLAZGOS §1): hoy el modo real envía el evento sin firmar); invalidar eventos para FEX/FSE; revertir físicamente una devolución. 147 tests pasan.

### 2.8 Factura de Exportación (11) y Factura de Sujeto Excluido (14) (construido 2026-11-20)
**Exportación** (`ExportacionBuilder`, esquema fe-fex-v3, JSON v3):
- Se vende desde una venta con `tipo_dte = 11` y cliente del extranjero. `CreateSale` y el POS calculan **sin IVA** (opción `exportacion` en `DteCalculationService`); no hay retenciones. Cada ítem va como venta gravada con tributo `C3` (IVA 0 %), el resumen trae el tributo C3 con valor 0,00 y `montoTotalOperacion` = gravada + seguro + flete.
- **Datos nuevos:** cliente → código de país CAT-020, país, tipo de persona CAT-029 (formulario de clientes), más tipo de documento Pasaporte/Carné/Otro y actividad y dirección; empresa → en DTE > Administración: tipo de exportación (1 bienes, 2 servicios), recinto fiscal CAT-027, tipo de régimen CAT-033, régimen CAT-028 e INCOTERMS CAT-031 opcional (`dte_settings.expo_*`). Con servicios, recinto/tipo de régimen/régimen salen null (regla de la Normativa). Sin esos datos o sin país del cliente no se emite.
- **Límites:** seguro y flete van en 0 (no hay dónde capturarlos aún); sin documentos relacionados ni "otros documentos"; sin retorno de exportación. Los códigos de recinto/régimen no se validan contra el catálogo (transcripción de terceros): se confía en lo que capture el contador.

**Sujeto excluido** (`SujetoExcluidoBuilder`, esquema fe-fse-v2, JSON v2):
- La emite **la empresa al comprar** a un proveedor que no es contribuyente. Botón en el detalle de la compra → pantalla donde se captura la dirección del vendedor (departamento/municipio/distrito/dirección, validados con el mapa del Decreto 762 y guardados en el proveedor) y la retención de renta opcional. Sin IVA: `totalPagar = subTotal − reteRenta`. Documento del vendedor: DUI (13) y si no NIT (36) del proveedor, sin guiones.
- El DTE queda ligado a la compra (`dtes.purchase_id`, `sale_id` null); una compra no admite dos facturas vigentes. La firma/transmisión reutiliza el flujo común (con un contenedor de venta solo para empresa/certificado).
- **Invalidación** desde la misma pantalla de la compra (rescindir; error/otro exige otra compra con su factura aceptada como reemplazo; plazo 3 meses). Ambos tipos se invalidan con el evento de invalidación validado contra el esquema.
- **Límites:** la compra ya está registrada (inventario y cuentas por pagar existen aparte): emitir o invalidar la factura no los modifica. Retención de renta: se captura a mano (no hay regla automática del 10 %: confirmar con el contador cuándo aplica). Sin Evento de Retorno para FSE/FEX (el constructor solo acepta Facturas).
- Migraciones: `customers` país/tipo de persona, `providers` departamento/municipio/distrito, `dte_settings.expo_*`, `dtes.purchase_id`. 170 tests pasan.

### 2.9 Checklist de pruebas de homologación (2026-11-20)
`php artisan dte:pruebas-homologacion` genera el mínimo oficial de documentos sintéticos por tipo (Manual §XXIV: 90 Facturas, 75 CCF, 50 NC, 25 ND, 90 FEX, 25 FSE, 5 invalidaciones, 5 retornos), los valida contra el esquema y recalcula su aritmética (`VerificadorAritmetico`); `--exportar` los guarda como JSON. Resultado: **360 documentos, todos válidos** (probado con 6 semillas). Detalle, requisitos de la prueba real y pendientes en **`PRUEBAS_HOMOLOGACION.md`**. Hallazgo importante: Stockia emite en contingencia pero **no construye el Evento de Contingencia** que Hacienda exige después (24 h / lote 72 h). Sin constructor: 04, 07, 08, 09, 15 y los eventos de contingencia y operaciones especiales. 179 tests pasan.

### 2.10 Evento de Contingencia (construido 2026-11-20)
- **Ciclo de tres momentos (Manual §VIII):** (1) con el modo de integración en *contingencia* los documentos salen con modelo/operación 2 y estado CONTINGENCIA y se agrupan en una contingencia **por establecimiento** (`dte_contingencias.establecimiento`); (2) `DteEmissionService::cerrarContingencia` arma, valida (esquema `contingencia` v4), firma y transmite el **Evento de Contingencia** (1–1000 documentos; tipo y motivo CAT-005; responsable = el configurado en DTE > Administración; en real es obligatorio); (3) con el sello del evento, `enviarLoteContingencia` transmite los documentos dentro de **72 h** y cierra la contingencia. Estados: ABIERTA → EVENTO_ENVIADO → CERRADA, o EVENTO_RECHAZADO (se corrige y reintenta).
- Reglas: no se transmite el evento mientras el modo siga en *contingencia*; el lote exige el evento sellado y no admite vencido el plazo; un evento no se repite; los documentos no se modifican entre la emisión y el lote (el manual exige conservar tipo y motivo).
- **Antes estaba mal:** el "reenvío" mandaba los documentos directo (sin evento) y siempre por el simulador aunque el modo fuera real, y cada documento nuevo en contingencia reiniciaba la fecha de inicio. Corregido.
- Interfaz: tabla "Contingencias" en DTE > Administración con botones evento/lote; `dte:reenviar-contingencia` hace evento + lote y avisa lo que no pudo. Fechas de la contingencia guardadas en UTC explícito (la app cambia la zona por empresa en cada petición web).
- **Límites:** (a) los documentos emitidos en contingencia se "firman" con la firma simulada hasta que exista el firmador real (deben firmarse de verdad al emitirlos); (b) el plazo de 24 h del evento no se vigila automáticamente (no sabemos cuándo cesó la causa: lo marca quien lo ejecuta); (c) el endpoint del evento y del lote en modo real usa `send_url`: el contrato correcto está en el Manual Técnico de Integración (`/fesv/contingencia`, `/fesv/recepcionlote`; ver FASE0_HALLAZGOS §1) y falta implementarlo; (d) solo tipo 1 de contingencia en la emisión automática (no hay selector de causa). 191 tests pasan.

**Supuestos por cotejar con el Anexo IV/catálogos (no verificados contra el PDF):**
- (Verificado después contra el catálogo: CAT-017 `01` y CAT-018 `01` correctos.) Stockia solo distingue efectivo/crédito; tarjeta, transferencia, etc. salen como efectivo hasta que existan en el POS.
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
1. Identificación obligatoria sobre el monto legal (pendiente del contador). Migrar y probar con datos reales de emisor/cliente en el servidor (la migración de `customers` aún no está aplicada en ningún lado).
2. Captura de seguro/flete y INCOTERMS por venta de exportación; Evento de Retorno para FEX/FSE.
3. Firma (firmador oficial por cliente), transmisión, reintentos y eventos.

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
