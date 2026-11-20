<?php

namespace Stockia\DteSvMh\Services\Contracts;

use Stockia\DteSvMh\Models\DteSetting;

interface AuthInterface
{
    public function authenticate(DteSetting $settings): array;

    /** Descarta el token guardado (p. ej. cuando Hacienda responde 401) para pedir uno nuevo. */
    public function olvidar(DteSetting $settings): void;
}
