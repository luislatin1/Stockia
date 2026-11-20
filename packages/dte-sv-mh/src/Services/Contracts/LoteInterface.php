<?php

namespace Stockia\DteSvMh\Services\Contracts;

use Stockia\DteSvMh\Models\DteSetting;

/**
 * Recepción por lotes y consulta de lote (Manual Técnico §3.4, §4.2.2 y §4.3.2). Es el camino de los documentos
 * emitidos en contingencia: máximo 100 por lote y respuesta NO inmediata (se consulta con el código de lote).
 */
interface LoteInterface
{
    public const MAXIMO_POR_LOTE = 100;

    /**
     * @param  array<int, array>  $firmados  salidas del firmador (jsonFirmado + payload)
     * @return array{recibido:bool, codigoLote:?string, response:?array, error:?string}
     */
    public function enviarLote(array $firmados, DteSetting $settings, string $nitEmisor, string $idEnvio): array;

    /**
     * @return array{procesados:array<string,string>, rechazados:array<string,string>, response:?array}
     *         procesados: codigoGeneracion => sello; rechazados: codigoGeneracion => motivo
     */
    public function consultarLote(DteSetting $settings, string $codigoLote): array;
}
