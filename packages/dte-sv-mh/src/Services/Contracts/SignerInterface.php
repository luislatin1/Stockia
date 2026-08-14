<?php

namespace Stockia\DteSvMh\Services\Contracts;

use App\Models\Sale;
use Stockia\DteSvMh\Models\DteSetting;

interface SignerInterface
{
    public function sign(array $dte, Sale $sale, DteSetting $settings): array;
}
