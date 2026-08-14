<?php

namespace Stockia\DteSvMh\Services\Contracts;

use Stockia\DteSvMh\Models\Dte;
use Stockia\DteSvMh\Models\DteSetting;

interface InvalidationInterface
{
    public function invalidate(Dte $dte, DteSetting $settings, array $payload = []): array;
}
