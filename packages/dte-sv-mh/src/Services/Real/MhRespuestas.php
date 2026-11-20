<?php

namespace Stockia\DteSvMh\Services\Real;

/**
 * Lectura de las respuestas del Sistema de Transmisión y catálogo de los códigos más frecuentes
 * (Manual Técnico de Integración v2.0, "Códigos de respuesta del servicio de recepción"; texto por OCR).
 * Los códigos 001/002 son aceptación; el resto, rechazo.
 */
final class MhRespuestas
{
    /** @var array<string,string> */
    private const CODIGOS = [
        '001' => 'Recibido',
        '002' => 'Recibido con observaciones',
        '003' => 'Valor no válido',
        '004' => 'Ya existe un registro con ese valor',
        '005' => 'Debe proporcionar un valor',
        '006' => 'Formato no válido',
        '007' => 'Valor no permitido',
        '009' => 'El NIT del contribuyente no existe',
        '010' => 'El contribuyente no está activo',
        '011' => 'No es contribuyente de IVA',
        '012' => 'El documento se encuentra invalidado',
        '014' => 'El monto es mayor al del documento relacionado',
        '015' => 'Fecha no correcta',
        '016' => 'Fecha fuera del plazo permitido',
        '017' => 'Error en el correlativo',
        '018' => 'Cálculo incorrecto',
        '020' => 'Servicio de validación de firma no disponible',
        '027' => 'Documento sin sello',
        '028' => 'Excede la holgura de 30 minutos',
        '029' => 'Parámetros no válidos',
        '030' => 'Contribuyente no autorizado para emitir este tipo de DTE',
        '031' => 'El documento no cumple la normativa de cumplimiento',
        '098' => 'Los datos no cumplen el estándar JSON',
        '099' => 'Error no catalogado',
        '105' => 'Token inválido',
        '106' => 'Token requerido',
    ];

    public static function descripcion(?string $codigo): ?string
    {
        return $codigo !== null ? (self::CODIGOS[$codigo] ?? null) : null;
    }

    /**
     * Normaliza la respuesta de recepción/consulta de un DTE.
     *
     * @return array{status:string, sello:?string, response:?array, error:?string, observaciones:array}
     */
    public static function documento(?array $json, int $http): array
    {
        $json ??= [];
        $estado = strtoupper((string) ($json['estado'] ?? ''));
        $sello = $json['selloRecibido'] ?? null;
        $obs = array_values(array_filter((array) ($json['observaciones'] ?? [])));

        if ($estado === 'PROCESADO' && $sello) {
            return ['status' => 'ACEPTADO', 'sello' => (string) $sello, 'response' => $json, 'error' => null, 'observaciones' => $obs];
        }

        return [
            'status' => 'RECHAZADO',
            'sello' => null,
            'response' => $json ?: null,
            'error' => self::mensajeDeRechazo($json, $http),
            'observaciones' => $obs,
        ];
    }

    public static function mensajeDeRechazo(array $json, int $http): string
    {
        $codigo = isset($json['codigoMsg']) ? (string) $json['codigoMsg'] : null;
        $texto = trim((string) ($json['descripcionMsg'] ?? '')) ?: (self::descripcion($codigo) ?? 'Rechazado por Hacienda');
        $partes = [($codigo ? "[{$codigo}] " : '').$texto];
        foreach ((array) ($json['observaciones'] ?? []) as $o) {
            if ($o !== null && $o !== '') {
                $partes[] = (string) $o;
            }
        }
        if (! $json) {
            $partes = ["Respuesta vacía de Hacienda (HTTP {$http})"];
        }

        return implode(' | ', $partes);
    }
}
