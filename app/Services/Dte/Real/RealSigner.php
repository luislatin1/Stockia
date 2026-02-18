<?php

namespace App\Services\Dte\Real;

use App\Models\DteSetting;
use App\Models\Sale;
use App\Services\Dte\Contracts\SignerInterface;
use Illuminate\Support\Facades\Http;

class RealSigner implements SignerInterface
{
    public function sign(array $dte, Sale $sale, DteSetting $settings): array
    {
        if (! $settings->signer_url) {
            throw new \RuntimeException('Modo real configurado sin signer_url.');
        }

        $certificate = $this->resolveCertificate($sale, $settings);

        $response = Http::timeout(20)->post($settings->signer_url, [
            'certificate' => $certificate,
            'payload' => $dte,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('No fue posible firmar DTE en modo real.');
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new \RuntimeException('Servicio de firma devolvió respuesta inválida.');
        }

        if (! isset($json['payload'])) {
            $json['payload'] = $dte;
        }

        return $json;
    }

    private function resolveCertificate(Sale $sale, DteSetting $settings): string
    {
        if ($settings->use_dummy_certificate) {
            return (string) ($settings->dummy_certificate_text ?: 'CERT-DUMMY');
        }

        $companyCert = (string) ($sale->company->certificado_firma ?? '');
        if ($companyCert === '') {
            throw new \RuntimeException('No hay certificado de firma configurado para modo real.');
        }

        return $companyCert;
    }
}
