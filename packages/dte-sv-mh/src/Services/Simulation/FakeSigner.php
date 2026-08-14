<?php

namespace Stockia\DteSvMh\Services\Simulation;

use App\Models\Sale;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\Contracts\SignerInterface;

class FakeSigner implements SignerInterface
{
    public function sign(array $dte, Sale $sale, DteSetting $settings): array
    {
        $json = json_encode($dte, JSON_UNESCAPED_UNICODE);

        return [
            'jsonFirmado' => base64_encode((string) $json),
            'firmaSimulada' => hash('sha256', (string) $json),
            'fechaFirma' => now()->toDateTimeString(),
            'certificadoUsado' => $settings->use_dummy_certificate
                ? (string) ($settings->dummy_certificate_text ?: 'CERTIFICADO-DUMMY')
                : (string) ($sale->company->certificado_firma ?: 'CERTIFICADO-DUMMY'),
            'codigoGeneracion' => $dte['identificacion']['codigoGeneracion'] ?? null,
            'payload' => $dte,
        ];
    }
}
