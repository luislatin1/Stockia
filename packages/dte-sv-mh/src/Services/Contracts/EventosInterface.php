<?php

namespace Stockia\DteSvMh\Services\Contracts;

use Stockia\DteSvMh\Models\DteSetting;

/** Envío de eventos que no son DTE: hoy, el Evento de Contingencia (Manual Técnico §4.4). */
interface EventosInterface
{
    /**
     * @param  array  $firmado  salida del firmador: jsonFirmado (JWS) y payload (el evento)
     * @return array{status:string, sello:?string, response:?array, error:?string}
     */
    public function enviarContingencia(array $firmado, DteSetting $settings): array;
}
