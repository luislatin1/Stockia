<?php

namespace App\Services\Dte\Contracts;

use App\Models\Dte;
use App\Models\DteSetting;

interface InvalidationInterface
{
    public function invalidate(Dte $dte, DteSetting $settings, array $payload = []): array;
}
