<?php

namespace Stockia\DteSvMh\Services\Real;

/** Hacienda no respondió (o respondió con error de servidor): activa la política de reintentos. */
class MhSinRespuestaException extends \RuntimeException
{
}
