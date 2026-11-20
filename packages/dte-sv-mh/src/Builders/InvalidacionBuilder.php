<?php

namespace Stockia\DteSvMh\Builders;

use Carbon\CarbonInterface;

/**
 * Evento de Invalidación (esquema invalidacion-schema-v3), común a todos los tipos de DTE y eventos.
 *
 * Reglas (Manual Funcional v2.0 §VII):
 *  - tipoAnulacion (CAT-024): 1 error en la información, 2 rescindir la operación, 3 otro (incluye cambio de bienes).
 *  - `codigoGeneracionR` = código del documento que reemplaza al invalidado; null en rescisión (2) y siempre null
 *    al invalidar Notas de Crédito, Eventos de Retorno y demás eventos.
 *  - Fecha del evento: para CCF, NC, ND y eventos es la MISMA fecha de generación del documento invalidado;
 *    para Factura puede ser hasta 3 meses después de su generación (aquí: hoy).
 *  - Los eventos (tipo 18) no tienen número de control: va null.
 *
 * Entrada ($ctx): ambiente, codigo_generacion, fecha (CarbonInterface), emisor{nit,nombre,codEstableMH,codEstable,
 *  codPuntoVentaMH,codPuntoVenta,telefono,correo}, documento{tipo_dte,codigo_generacion,sello,numero_control,
 *  fecha_emision,reemplazo,receptor?{tipoDocumento,numDocumento,nombre,telefono,correo}},
 *  motivo{tipo,texto,responsable{nombre,tipo_doc,num_doc},solicita{nombre,tipo_doc,num_doc}}
 */
class InvalidacionBuilder
{
    public function build(array $ctx): array
    {
        $d = $ctx['documento'];
        $m = $ctx['motivo'];
        $tipo = (int) $m['tipo'];

        if (! in_array($tipo, [1, 2, 3], true)) {
            throw new \RuntimeException('Tipo de invalidación no válido (1, 2 o 3).');
        }

        $reemplazo = $d['reemplazo'] ?? null;
        if ($tipo === 2 || ! $reemplazo) {
            $reemplazo = null;
        }

        /** @var CarbonInterface $fecha */
        $fecha = $ctx['fecha'];
        // Todo documento que no sea Factura/FEX/FSE se invalida con la fecha de su propia generación.
        $fechaEvento = in_array((string) $d['tipo_dte'], ['01', '11', '14'], true)
            ? $fecha->format('Y-m-d')
            : (string) $d['fecha_emision'];

        $r = $d['receptor'] ?? null;
        $num = $d['numero_control'] ?? null;

        return [
            'identificacion' => [
                'version' => 3,
                'ambiente' => (string) $ctx['ambiente'],
                'codigoGeneracion' => strtoupper((string) $ctx['codigo_generacion']),
                'fecEmi' => $fechaEvento,
                'horEmi' => $fecha->format('H:i:s'),
                'fusion' => null,
            ],
            'emisor' => $ctx['emisor'],
            'documento' => [
                'tipoDte' => (string) $d['tipo_dte'],
                'codigoGeneracion' => strtoupper((string) $d['codigo_generacion']),
                'selloRecibido' => (string) $d['sello'],
                'numeroControl' => $num && strlen((string) $num) === 31 ? (string) $num : null,
                'fecEmi' => (string) $d['fecha_emision'],
                'codigoGeneracionR' => $reemplazo ? strtoupper((string) $reemplazo) : null,
                'tipoDocumento' => $r['tipoDocumento'] ?? null,
                'numDocumento' => $r['numDocumento'] ?? null,
                'nombre' => $r['nombre'] ?? null,
                'telefono' => $r['telefono'] ?? null,
                'correo' => $r['correo'] ?? null,
            ],
            'motivo' => [
                'tipoAnulacion' => $tipo,
                'motivoAnulacion' => mb_substr(trim((string) ($m['texto'] ?? '')), 0, 200) ?: null,
                'nombreResponsable' => $m['responsable']['nombre'],
                'tipDocResponsable' => $m['responsable']['tipo_doc'],
                'numDocResponsable' => $m['responsable']['num_doc'],
                'nombreSolicita' => $m['solicita']['nombre'],
                'tipDocSolicita' => $m['solicita']['tipo_doc'],
                'numDocSolicita' => $m['solicita']['num_doc'],
            ],
        ];
    }
}
