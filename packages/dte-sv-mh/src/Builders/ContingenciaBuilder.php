<?php

namespace Stockia\DteSvMh\Builders;

use Carbon\CarbonInterface;

/**
 * Evento de Contingencia (esquema contingencia-schema-v4). Manual Funcional v2.0 §VIII:
 *  - Se transmite dentro de las 24 h siguientes al cese de la causa y es requisito para enviar el lote.
 *  - Informa de 1 a 1000 documentos (por código de generación) generados fuera de línea.
 *  - El tipo y el motivo de contingencia deben coincidir con los de los documentos (CAT-005; el motivo es
 *    obligatorio solo con el tipo 5 "otro", y se puede ampliar en los demás).
 *  - Nombre y documento del responsable del establecimiento son obligatorios.
 *
 * Entrada ($ctx): ambiente, codigo_generacion, fecha (momento de la transmisión), inicio y fin (CarbonInterface,
 *  hora de El Salvador), tipo_contingencia (1-5), motivo ?string,
 *  emisor{nit,nombre,responsable{nombre,tipo_doc,num_doc},tipo_establecimiento,codEstableMH,codPuntoVentaMH,telefono,correo},
 *  documentos[{tipo_dte, codigo_generacion}]
 */
class ContingenciaBuilder
{
    public function build(array $ctx): array
    {
        $docs = array_values($ctx['documentos'] ?? []);
        if ($docs === []) {
            throw new \RuntimeException('El evento de contingencia necesita al menos un documento.');
        }
        if (count($docs) > 1000) {
            throw new \RuntimeException('Un evento de contingencia admite como máximo 1000 documentos: divida el lote.');
        }

        $tipo = (int) $ctx['tipo_contingencia'];
        if ($tipo < 1 || $tipo > 5) {
            throw new \RuntimeException('Tipo de contingencia no válido (CAT-005: 1 a 5).');
        }
        $motivo = trim((string) ($ctx['motivo'] ?? ''));
        if ($tipo === 5 && $motivo === '') {
            throw new \RuntimeException('Con tipo de contingencia 5 (otro) es obligatorio explicar el motivo.');
        }

        /** @var CarbonInterface $inicio */
        $inicio = $ctx['inicio'];
        /** @var CarbonInterface $fin */
        $fin = $ctx['fin'];
        if ($fin->lessThan($inicio)) {
            throw new \RuntimeException('La contingencia no puede terminar antes de empezar.');
        }

        /** @var CarbonInterface $fecha */
        $fecha = $ctx['fecha'];
        $e = $ctx['emisor'];
        $r = $e['responsable'];

        return [
            'identificacion' => [
                'version' => 4,
                'ambiente' => (string) $ctx['ambiente'],
                'codigoGeneracion' => strtoupper((string) $ctx['codigo_generacion']),
                'fTransmision' => $fecha->format('Y-m-d'),
                'hTransmision' => $fecha->format('H:i:s'),
            ],
            'emisor' => [
                'nit' => $e['nit'],
                'nombre' => $e['nombre'],
                'nombreResponsable' => $r['nombre'],
                'tipoDocResponsable' => $r['tipo_doc'],
                'numeroDocResponsable' => $r['num_doc'],
                'tipoEstablecimiento' => $e['tipo_establecimiento'],
                'codEstableMH' => $e['codEstableMH'] ?? null,
                'codPuntoVentaMH' => $e['codPuntoVentaMH'] ?? null,
                'telefono' => $e['telefono'],
                'correo' => $e['correo'],
            ],
            'detalleDTE' => array_map(fn ($d, $i) => [
                'noItem' => $i + 1,
                'tipoDoc' => (string) $d['tipo_dte'],
                'codigoGeneracion' => strtoupper((string) $d['codigo_generacion']),
            ], $docs, array_keys($docs)),
            'motivo' => [
                'fInicio' => $inicio->format('Y-m-d'),
                'fFin' => $fin->format('Y-m-d'),
                'hInicio' => $inicio->format('H:i:s'),
                'hFin' => $fin->format('H:i:s'),
                'tipoContingencia' => $tipo,
                'motivoContingencia' => $motivo === '' ? null : mb_substr($motivo, 0, 500),
            ],
        ];
    }
}
