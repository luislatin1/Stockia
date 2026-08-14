<?php

namespace Stockia\DteSvMh\Services\Contracts;

use Stockia\DteSvMh\Models\DteSetting;

interface AuthInterface
{
    public function authenticate(DteSetting $settings): array;
}
