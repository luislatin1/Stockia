<?php

namespace Stockia\DteSvMh\Services\Contracts;

use Stockia\DteSvMh\Models\DteSetting;

/** Consulta del estado de un DTE ya transmitido (Manual Técnico §4.3.1); base de la política de reintentos. */
interface ConsultaInterface
{
    /** @return array{status:string, sello:?string, response:?array, error:?string, observaciones:array} */
    public function consultarDte(DteSetting $settings, string $nitEmisor, string $tipoDte, string $codigoGeneracion): array;
}
