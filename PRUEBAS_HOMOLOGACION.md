# Checklist de la prueba de homologación (DTE 2.0)

Fuente de los mínimos: Manual Funcional del Sistema de Transmisión v2.0, §XXIV "Pruebas mínimas de transmisión".
Estado al 2026-11-20 · rama `fase1-dte-v2`.

## Qué hace el comando

```bash
php artisan dte:pruebas-homologacion                       # mínimos oficiales de todos los tipos soportados
php artisan dte:pruebas-homologacion --tipo=01 --tipo=03   # solo algunos
php artisan dte:pruebas-homologacion --cantidad=500        # estrés: más documentos por tipo
php artisan dte:pruebas-homologacion --semilla=7           # otros datos (misma semilla = mismos datos)
php artisan dte:pruebas-homologacion --exportar=storage/app/homologacion   # guarda cada documento como JSON + resumen.json
```

Por cada tipo genera el mínimo de documentos **sintéticos y variados** (ventas gravadas/exentas/no sujetas, descuentos,
decimales largos, contado y crédito, con y sin receptor, retenciones, contingencia) con **los mismos constructores del
sistema**, y comprueba cada uno dos veces:

1. **Esquema oficial** de Hacienda (los 15 JSON versionados).
2. **Aritmética** (`VerificadorAritmetico`): recalcula desde el propio JSON cada monto del cuerpo (precio × cantidad − descuento),
   el IVA por ítem y cada total del resumen contra la suma de los ítems, con la holgura de ±0,01 de la Normativa. Es lo que
   Hacienda valida además del esquema.

No toca la base de datos. Código de salida 0 si todo pasa, 1 si algo falla, 2 si piden un tipo sin constructor.

> **Alcance real:** prueba **estructura y cálculo**. No prueba la transmisión: el sello de recepción solo se obtiene
> firmando y enviando al ambiente de pruebas de Hacienda, y eso sigue pendiente (ver "Requisitos").

## Cobertura

| Clave | Documento | Mínimo | Constructor | Estado de la prueba local |
|---|---|---:|---|---|
| 01 | Factura | 90 | sí | **OK** |
| 03 | Comprobante de Crédito Fiscal | 75 | sí | **OK** |
| 05 | Nota de Crédito | 50 | sí | **OK** |
| 06 | Nota de Débito | 25 | sí | **OK** |
| 11 | Factura de Exportación | 90 | sí | **OK** |
| 14 | Factura de Sujeto Excluido | 25 | sí | **OK** |
| invalidacion | Evento de Invalidación | 5 | sí | **OK** |
| retorno | Evento de Retorno | 5 | sí | **OK** |
| 04 | Nota de Remisión | 50 | **no** | pendiente |
| 07 | Comprobante de Retención | 50 | **no** | pendiente |
| 08 | Comprobante de Liquidación | 75 | **no** | pendiente |
| 09 | Documento Contable de Liquidación | 50 | **no** | pendiente |
| 15 | Comprobante de Donación | 25 | **no** | pendiente |
| contingencia | Evento de Contingencia | 5 | sí | **OK** |
| operaciones_especiales | Evento de Operaciones Especiales | 5 | **no** | pendiente |

Los tipos sin constructor **no los emite Stockia hoy**. La homologación probablemente solo exige los tipos que el
contribuyente va a usar (el manual no lo aclara: confirmar con Hacienda qué subconjunto se exige a cada cliente).

**Contingencia (resuelta 2026-11-20):** Stockia emite en contingencia y ahora también regulariza el ciclo completo
(Manual §VIII): momento 2, **Evento de Contingencia** (≤ 24 h del cese) y momento 3, **lote** de documentos (≤ 72 h del sello del evento).
Se opera desde DTE > Administración (botones "Transmitir evento" y "Transmitir lote") o con `php artisan dte:reenviar-contingencia`.
Antes hay que volver el modo de integración a Real (o Simulación).

## Requisitos para la prueba real (nada de esto lo da este comando)

| # | Requisito | Estado |
|---|---|---|
| 1 | Credenciales y **certificado del ambiente de pruebas** (ambiente `00`) de cada cliente | pendiente (de terceros) |
| 2 | **Firmador oficial** por cliente (Docker en el servidor, puerto 8113) | pendiente (necesita sudo) |
| 3 | **Manual Tecnológico** de integración (firma, endpoints, reintentos, autenticación) | no recibido |
| 4 | Códigos de **establecimiento y punto de venta asignados por Hacienda** confirmados (`codigo_confirmado`) | pendiente |
| 5 | Datos del emisor completos: distrito, teléfono y correo (hoy vacíos en la empresa real) | pendiente |
| 6 | **Responsable de eventos** (nombre y documento) en DTE > Administración | pendiente |
| 7 | Exportación: tipo, recinto, régimen, INCOTERMS en DTE > Administración (si el cliente exporta) | pendiente |
| 8 | Comando que **firme y transmita** los JSON exportados y registre sello/estado de cada uno | por construir cuando existan 1–3 |

## Cómo se correrá la prueba real (propuesta)

1. `dte:pruebas-homologacion --exportar=<carpeta>` → JSON sin firmar, uno por documento, con `resumen.json`.
2. Cuando existan firmador y credenciales: un comando firma cada JSON, lo transmite al ambiente de pruebas y guarda el
   sello o los errores en una tabla de resultados por tipo.
3. Corregir lo que Hacienda rechace (sus validaciones de catálogo y de reglas del Anexo IV pueden ser más estrictas que el esquema)
   y repetir hasta cumplir el mínimo de aceptados por tipo.
4. Las pruebas de Notas de Crédito/Débito necesitan CCF aceptados reales para relacionarlos: la secuencia debe ir
   CCF → NC/ND, y Factura → Evento de Retorno, usando los códigos de generación devueltos.

## Lo que no está verificado

- Si los mínimos del manual cuentan **documentos transmitidos** o **aceptados**, y si se exigen por tipo que el cliente no use.
- Que las validaciones de Hacienda coincidan con el esquema y la aritmética de este verificador (la Normativa Anexo IV se leyó por OCR,
  incompleta); este comando no sustituye al ambiente de pruebas.
- Los catálogos (CAT-014, 017, 019, 020, 027, 028, 031…) vienen de una transcripción de terceros: los códigos usados en los datos
  de prueba son plausibles, no cotejados uno a uno contra el PDF oficial.
- Las pruebas usan un emisor y receptores **ficticios**; en la prueba real irán los del cliente.
