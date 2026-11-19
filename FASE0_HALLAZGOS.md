# Fase 0 — Verificación y decisiones previas (DTE 2.0)

Fecha: 2026-11-19 · Complementa `REPORTE_AUDITORIA.md` · Sin cambios de código.

## Estado de las tareas

| # | Tarea | Estado |
|---|---|---|
| 0.1 | OCR del Manual Técnico v2 y de la Normativa v2.0; cerrar contrato HTTP | ✅ Hecho (ver §1–§3). Anexos II–V (estructura y validaciones por campo) quedan OCR-eados pero **no leídos aún**: se leen en Fase 2 por tipo de DTE |
| 0.2 | Confirmar fecha límite y reglas de transición | 🟡 **Sin confirmar oficialmente** (ver §5). Necesita acción suya |
| 0.3 | Definir qué tipos de DTE se usan | ⏳ **Pendiente de su decisión** (ver §6) |
| 0.4 | Modelo de ubicación departamento/municipio/distrito | ✅ Diagnóstico hecho; queda **un dato externo** por conseguir (ver §4) |

Archivos nuevos: `ExportPlugins/DTE v2/ocr_texto/` con `Manual_Tecnico_v2.ocr.txt`, `Normativa_v2.ocr.txt` y `ocr.ps1` (script de OCR con Windows OCR, sin instalar nada). Es OCR: puede tener errores de caracteres, no lo cite como texto oficial sin cotejar el PDF.

---

## 1. Contrato HTTP oficial vs. lo que hace Stockia hoy

Fuente: Manual Técnico v2 (págs. 6–32). Confirma y detalla las sospechas del reporte.

| Servicio | Contrato oficial | Stockia hoy (`packages/dte-sv-mh`) | Veredicto |
|---|---|---|---|
| **Autenticación** | `POST https://apitest.dtes.mh.gob.sv/seguridad/auth` (prod: `api.dtes.mh.gob.sv`). Headers: `content-type: application/x-www-form-urlencoded`, `User-Agent`. Body: `user`, `pwd`. Respuesta: `{status, body:{user, token:"Bearer …", rol…}}`. Token válido **48 h en pruebas, 24 h en producción**. Contraseña de 13–25 caracteres con letras, números y un especial | `RealAuthService`: JSON con `{user, password, ambiente}`; lee `$payload['token']` en el nivel raíz (en la respuesta real está en `body.token`) y asume `expires_in` de 3600 s | ❌ Incompatible en 3 puntos (formato, nombre `pwd`, ruta del token) |
| **Firma** | Servicio local (Docker con/sin SSL o servicio Windows) en puerto 8113, `…/firmardocumento/`. Body JSON: `nit`, `activo`(bool), `passwordPri`, `dteJson`. Respuesta `{status:"OK", body:"<JWS>"}` o `{status:"ERROR", body:{codigo, mensaje[]}}`. **El certificado `.crt` vive en el firmador** (carpeta `certificado/`, nombrado con el NIT), no viaja en la petición | `RealSigner`: envía `{certificate, payload}` | ❌ Incompatible. Además Stockia no necesita guardar el certificado, solo `passwordPri` (cifrada) |
| **Recepción 1 a 1** | `POST …/fesv/recepciondte`. Headers: `Authorization`(token ya con "Bearer"), `User-Agent`, `content-type: application/JSON`. Body: `ambiente`, `idEnvio`(int, correlativo propio), `version`(= `identificacion.version`), `tipoDte`, `documento`(**string JWS firmado**), `codigoGeneracion`. Respuesta: `estado` PROCESADO/RECHAZADO, `selloRecibido`, `observaciones[]`, `codigoMsg`. **Rechazo = HTTP 400** | `RealSendService`: `withToken($token)` (duplicaría "Bearer") y envía el array firmado directo | ❌ Incompatible |
| **Recepción por lote** | `POST …/fesv/recepcionlote`. Body: `ambiente`, `idEnvio`(UUID v4 mayúsc.), `version`, `nitEmisor`, `documentos[]`. Máx. **100 DTE por lote**, máx. 400 lotes. Responde `RECIBIDO` + `codigoLote`; luego `GET …/fesv/recepcion/consultadtelote/{codigoLote}` | No existe | ❌ |
| **Consulta DTE** | `POST …/fesv/recepcion/consultadte` con `nitEmisor`, `tdte`, `codigoGeneracion` | No existe | ❌ (necesaria para la política de reintentos) |
| **Contingencia** | `POST …/fesv/contingencia`. Body: `nit`, `documento` (evento firmado) | Simulada en BD | ❌ |
| **Invalidación** | `POST …/fesv/anulardte`. Body: `ambiente`, `idEnvio`, `version`, `documento`(evento firmado) | `RealInvalidationService` postea a `send_url` (la URL de recepción) un cuerpo propio sin firma | ❌ |
| **Ambiente** | `"00"` = Pruebas, `"01"` = Producción | Default `DTE_AMBIENTE=00` | ✅ (correcto como default seguro) |
| **QR** | `https://admin.factura.gob.sv/consultaPublica?ambiente={}&codGen={}&fechaEmi={}` | No hay QR | ❌ |

Códigos de respuesta de recepción: el manual lista ~55 códigos (p. ej. 3 valor no válido, 18 cálculo incorrecto, 12 documento invalidado, 27 documento sin sello, 28 excede holgura de 30 min, 98 no cumple estándar JSON, 111 token requerido). Hay que mapearlos a mensajes de usuario en Fase 2.

**Implicación:** la capa `Real*` completa debe reescribirse; el patrón `Real*/Fake*` + `DteGatewayFactory` se conserva.

## 2. Reglas de la Normativa v2.0 que el MD no recogía o que corrigen el plan

| Regla (Normativa) | Qué implica para Stockia |
|---|---|
| **Rechazo (regla 10):** debe corregirse y retransmitirse dentro de **24 h** de comunicado el rechazo, **con el mismo Código de Generación** (salvo que el rechazo sea por ese código). Evitar retransmitir tras recibir respuesta | Hoy un `RECHAZADO` no tiene reintento y `emitForSale` generaría otro correlativo/código. Rediseñar: rechazo → estado "por corregir" con plazo de 24 h y reenvío con el mismo código. Esto **cambia la tarea 2.6** |
| **Política de reintentos (Manual 3.3):** si no hay respuesta en **8 s** → consultar estado (`consultadte`) → si no fue recibido, reenviar, hasta 2 veces → si persiste, contingencia. Hoy el timeout de envío es 25 s | Implementar en 2.5/2.6 |
| **Contingencia (13.2):** reintentar conexión **como mínimo cada 15 min**; si dura **más de 3 días**, presentar "Informe Técnico de Contingencia" a Hacienda antes del evento. Evento ≤ 24 h tras cesar; lote ≤ 72 h tras el sello del evento. Solo para CCF, NC, ND, CR, NR, FE, FEXE, FSEE (y Evento de Retorno). **Lotes de contingencia: 24/7** | Ajusta 2.11 |
| **Horario de lotes:** pruebas 08:00–17:00; producción 22:00–05:00, pero esta restricción es solo para quienes tienen *facturación cíclica*; contingencia sin restricción | Solo relevante si se usa lote normal |
| **Holgura de transmisión (Manual 3.1):** se aceptan documentos con fecha hasta 1 día posterior; el **último día del período tributario solo 30 min** de adelanto | Validar `fecEmi`/`horEmi` con reloj sincronizado (NTP); usar siempre la fecha actual, nunca futura |
| **Orden de transmisión (9.2):** lo que afecta, ajusta, disminuye o invalida debe transmitirse **después** del documento afectado | Dependencia NC/ND/Invalidación ← DTE original aceptado |
| **Archivo DTE (Anexo I 4.1):** JSON = estructura de datos **íntegra** + `firmaElectronica` (JWS) + `selloRecibido` (40 car. mayúsc.). Nombre de archivo = `<codigoGeneracion>.json` / `.pdf` | Hay que construir y entregar este archivo; hoy se guarda `json_firmado` como array arbitrario. Alterar el contenido se considera "lesivo al interés fiscal" |
| **Representación gráfica (11.3):** debe exportarse **desde los campos del JSON**, conservar los nombres de campo de los Anexos, y en **ambiente de pruebas debe llevar la advertencia de que carece de valor legal**. Un PDF no es un DTE | Confirma 2.8 (marca de simulación/pruebas) |
| **Tiquetes (regla 15):** desde el **1-ene-2025** no se pueden usar documentos equivalentes (tiquetes) con consumidores finales: debe emitirse Factura electrónica | Stockia aún ofrece ventas tipo "ticket" (`MODULOS.md`, `document_type`). **Consultar con el contador** si sigue siendo legal operar así; si no, el flujo POS debe emitir siempre DTE |
| **Declaración (10.2 / 7):** se declara por **fecha de generación**; documentos en estado transitorio deben registrarse contablemente; rechazados deben corregirse y ajustarse | Confirma 4.1 (libros por fecha de generación) |
| **Respaldo (Anexo I 6.1):** copia de seguridad de los JSON transmitidos con su sello. Requisitos de seguridad: cifrado de información sensible, logs, control de acceso, registro de accesos de usuarios y administradores, plan de continuidad | Confirma 1.4, 2.7 y 4.7 (bitácora) |
| **Infraestructura (Manual 6.4):** servidores y bases de datos del emisor deben estar en jurisdicciones con tratado de extradición y asistencia legal con El Salvador | **Restricción de hospedaje.** Revisar dónde se alojan los clientes (hoy LAN/local; si se piensa en nube, elegir región/proveedor con cuidado) y consultarlo con Hacienda |
| **Formato (Manual 6.1–6.2):** JSON sin `;` residuales, UTF-8; el JSON Schema es *referencial*, no sustituye las reglas lógicas y de cálculo; URLs **sin barra final** | Añadir a pruebas 3.1 |
| **Credenciales y llaves (9.2):** son exclusivas del contribuyente; compartirlas con terceros asume riesgo y sanción | Para un software que despliega a varios clientes: cada cliente con sus propias credenciales/certificado, nunca compartidos |
| **Número de control (Anexo I 1.3, 7.1.2):** 3.ª sección = 4 alfanuméricos de Casa Matriz/Sucursal/Bodega/Patio + 4 de Punto de Venta, "según la codificación establecida por la Administración Tributaria"; consecutivo centralizado o por establecimiento, **nunca por PV o tipo** (salvo resolución), reinicia cada ejercicio impositivo | Confirma el MD; el **código lo asigna Hacienda**, no se inventa (pregunta abierta nº 8 del reporte) |
| **Redondeo y holgura (7.2–7.3):** 8 decimales por ítem, 2 en resumen, **resumen calculado desde los decimales del ítem**, holgura ±0.01 | Confirma 2.3 |
| **Invalidación (13.1):** plazos del MD confirmados. Fecha del evento: del mismo día al día siguiente de la generación (FE/FEXE/FSEE: dentro de 3 meses). Si el motivo no es rescisión total, **primero** se genera y transmite el documento nuevo y el evento referencia ambos (excepto NC y CL). CCF y CR con sello que se modifican sin ser error → se ajustan con **NC/ND**, no se invalidan. Además hay un caso de **2 años para medicamentos perecederos** (FE/FEXE) | Matiza 2.10/2.12. Nota: el MD atribuye los 2 años al Evento de **Retorno** por actividad económica; la Normativa los ubica en **invalidación de medicamentos**. Reconciliar con el Manual Funcional si aplica al cliente |
| **Retorno (13.3):** FE, FEXE y FSEE, 3 meses, no es descuento ni puede exceder lo vendido; no invalida el original aunque cubra el 100 %; el propio evento sí puede invalidarse | Confirma 2.14 |
| **Fecha límite de adaptación (regla 18):** el plazo para usuarios de "Sistema de Transmisión" **lo establece la Administración Tributaria**. La Normativa no trae la fecha | Ver §5 |

## 3. Datos útiles del firmador oficial (`dte-firmador.zip`, ya íntegro)

- Incluye tres modalidades: `dockerConSSL`, `dockerSinSSL` y `servicioFirmadoWindows` (JDK 21 + WinSW).
- En Docker, el certificado `.crt` se copia a `certificado/` renombrado con el NIT; el puerto por defecto es 443→8113 (con SSL) y se cambia en `docker-compose.yml`. Variables en `svfe-api.env` (`SPRING_PROFILES_ACTIVE`, `SVFE_ARCHIVO`, `SVFE_PASSWORD`).
- Verificación de salud: `GET http://<ip>:8113/firmardocumento/status` → "Application is running".
- El zip trae un certificado de ejemplo (`Certificado_22222222222229.crt`, NIT de prueba): sirve para pruebas locales de integración, **nunca** para un cliente real.
- **Decisión de despliegue pendiente** (pregunta 4 del reporte): un firmador por cliente (recomendado: el certificado y la llave quedan en su infraestructura, coherente con la regla de credenciales exclusivas) vs. uno centralizado (compartiría llaves de terceros: evitar).

## 4. Tarea 0.4 — Modelo de ubicación

**Qué dicen los documentos**
- Catálogos oficiales (Excel `Catálogos – Facturación Electrónica`, v1.1, jul-2026): **CAT-012** Departamento (15 filas con "Otro"), **CAT-013** Municipio (44 + "Otro"), **CAT-008 Distrito** (262 + "Otro"). En el catálogo v1.2 que está en el repo (`DTE_catalogos_confirmados/CAT-008.json`) CAT-008 aparece **"eliminado"**: ya quedó obsoleto.
- Normativa (Anexo II/IV, emisor y receptor): `departamento` → CAT-012, `municipio` → CAT-013, `distrito` → CAT-008; **"el código de distrito debe tener correspondencia con departamento y municipio"** (el rechazo lo valida Hacienda). En el emisor es requerido; en el receptor de Factura es opcional pero, si se llena, debe ser coherente; "complemento" debe incluir el nombre del distrito. Teléfono del emisor: 8–30 caracteres.
- Los códigos **no son únicos globalmente**: en CAT-013 el código `14` es "Ahuachapán Centro" y también "Santa Ana Norte"; en CAT-008 el `01` se repite por departamento.

**Qué tiene Stockia**
- `dte_municipios.codigo` guarda `departamento+municipio` (4 caracteres, p. ej. `0114`) y el builder toma los 2 últimos al emitir (`normalizeMunicipioCode`). **Eso es correcto** para CAT-013. Corrijo una duda del reporte: **no hay mezcla de municipio y distrito en los datos**. Lo que falta es solo el distrito.
- No existe tabla ni columna de distrito (`companies`, `customers`).

**Hueco: la jerarquía distrito → municipio**
El Excel de CAT-008 lista los distritos **agrupados por departamento y sin indicar a qué municipio pertenece cada uno**. No se puede construir la relación solo con los catálogos del repo. Hace falta una fuente que mapee los 262 distritos a sus 44 municipios (la reforma territorial de 2023). Opciones:
1. Pedir a Hacienda/DGII el catálogo con jerarquía (o revisar `catalogo-ubicaciones-sv-dte-2026` y el *Sitio Emisores* por si lo exporta).
2. Construirla desde la fuente oficial de la reforma (Ley de Reorganización Territorial / CNR) y **verificarla contra el ambiente de pruebas**, que rechazará combinaciones incorrectas.

**Modelo propuesto** (para Fase 1.2)
- `dte_distritos(departamento_codigo, municipio_codigo, codigo, nombre)` con clave compuesta (departamento, municipio, distrito).
- Columnas `distrito` en `companies`, `customers` y en el futuro `establecimientos`.
- Selector en cascada departamento → municipio → distrito en los formularios (ya hay cascada departamento → municipio).
- Migración de datos existentes: los clientes actuales no tienen distrito; se deja `NULL` y el builder lo exige para el emisor y para el receptor cuando haya dirección; para consumidor final sin dirección (Factura) se envía `direccion: null`.
- Versionar los catálogos (versión + fecha) en la misma pasada.

## 5. Tarea 0.2 — Fecha límite: estado

- **Normativa v2.0:** no fija fecha; regla 18 remite al plazo "que les establezca la Administración Tributaria". El Manual Técnico tampoco la trae. (Confirma lo que ya decía el MD.)
- **Terceros:** una búsqueda web muestra varias fuentes (Edicom, vatupdate, LookupTax) que mencionan el **1-dic-2026** y cumplimiento obligatorio de la v2.0 para el Sistema de Transmisión. **Ninguna es oficial** y no pude abrir las páginas completas (`elsalvador.com` devolvió 502 y `vatupdate.com` 403). Un titular de `elsalvador.com` habla de un **"nuevo plazo"**, sin que yo haya podido leerlo; puede ser un cambio de fecha.
- **Qué falta (requiere a una persona):** confirmar por canal oficial. Sugerencias: revisar la notificación de Hacienda a cada cliente (el art. 119-A dice que Hacienda fija el grupo y la fecha por notificación individual), el *Sitio Emisores DTE* y factura.gob.sv.

**Preguntas para el contador / Hacienda**
1. ¿Cuál es la fecha asignada a cada cliente para v2.0, y hasta cuándo se acepta v1.2?
2. ¿Los tiquetes siguen siendo válidos para alguno de los clientes? (regla 15)
3. Plazo de conservación del art. 147 en años.
4. Comprobante de retención de renta (art. 156): ¿es DTE o no?
5. ¿Qué códigos de establecimiento y punto de venta (CAT-009 y portal de emisores) tiene asignado cada cliente?
6. ¿Algún cliente tiene resolución para numeración por punto de venta, facturación cíclica, Factura de Venta Simplificada o Comprobantes de Control Interno?

## 6. Tarea 0.3 — Decisión pendiente: tipos de DTE por alcance

Propuesta de partida (se ajusta con su respuesta). Pruebas mínimas que exige el manual: Factura 90, CCF 75, NC 50, ND 25, CR 50, FSE 25, Exportación 90, Remisión 50, Liquidación 75, Doc. Contable 50, Donación 25, y 5 por evento.

| Paquete | Tipos | Cuándo |
|---|---|---|
| **Mínimo operable (propuesto como Fase 2)** | Factura (01), CCF (03), Nota de Crédito (05) + eventos Invalidación y Contingencia | Cualquier comercio/POS |
| Segundo bloque | Nota de Débito (06), Comprobante de Retención (07, grandes contribuyentes agentes de retención), Factura de Sujeto Excluido (14) | Si el cliente compra a sujetos excluidos o retiene IVA |
| Bajo demanda | Nota de Remisión (04), Exportación (11), Liquidación (08/09), Donación (15), Eventos Retorno y Operaciones Especiales | Solo si un cliente lo usa |

## 7. Cambios al plan de acción por estos hallazgos

| Tarea | Cambio |
|---|---|
| 1.2 | Añadir tabla `dte_distritos` y conseguir la fuente de jerarquía (§4) |
| 1.4 | Almacenar solo `passwordPri` y credenciales API cifradas; el certificado vive en el firmador |
| 2.4 | Contrato de firma confirmado (§1); definir despliegue del firmador por cliente |
| 2.5 | Añadir cliente de consulta (`consultadte`, `consultadtelote`), token en caché 24/48 h con formato "Bearer" ya incluido, `idEnvio`, `User-Agent`, mapeo de códigos de respuesta |
| 2.6 | Reintentos con la política de 8 s + consulta + 2 reintentos; rechazo → "por corregir" en 24 h con **mismo código de generación**; no consumir otro correlativo |
| 2.7 | Construir el **Archivo DTE** (estructura + `firmaElectronica` + `selloRecibido`), nombrarlo por código de generación y guardar respaldo |
| 2.9 | QR con la URL de consulta pública; representación generada desde el JSON; advertencia en pruebas |
| 2.11 | Reintento cada 15 min, informe técnico si > 3 días, lotes de máx. 100 |
| Nueva | Revisar flujo POS "ticket" vs. regla 15 (con el contador) |
| Nueva | Revisar dónde se hospeda cada instalación (regla de jurisdicción, Manual 6.4) |

## 8. Siguiente paso

Para avanzar a la Fase 1 necesito sus respuestas a: (a) paquete de tipos DTE de §6, (b) si el firmador será uno por cliente, y (c) cómo conseguir la jerarquía distrito → municipio (§4). Las preguntas de §5 son para el contador y no bloquean el diseño, solo la fecha de entrega.
