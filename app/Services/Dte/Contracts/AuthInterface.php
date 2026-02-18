<?php

namespace App\Services\Dte\Contracts;

use App\Models\DteSetting;

interface AuthInterface
{
    public function authenticate(DteSetting $settings): array;
}
