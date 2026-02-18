<?php

namespace App\Services\Dte\Contracts;

use App\Models\DteSetting;

interface SendInterface
{
    public function send(array $signedDte, DteSetting $settings): array;
}
