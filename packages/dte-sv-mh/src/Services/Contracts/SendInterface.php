<?php

namespace Stockia\DteSvMh\Services\Contracts;

use Stockia\DteSvMh\Models\DteSetting;

interface SendInterface
{
    public function send(array $signedDte, DteSetting $settings): array;
}
