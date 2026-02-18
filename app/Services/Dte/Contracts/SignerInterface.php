<?php

namespace App\Services\Dte\Contracts;

use App\Models\DteSetting;
use App\Models\Sale;

interface SignerInterface
{
    public function sign(array $dte, Sale $sale, DteSetting $settings): array;
}
