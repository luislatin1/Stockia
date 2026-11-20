<?php

namespace Stockia\DteSvMh\Services\Real;

use App\Models\Sale;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\Contracts\SignerInterface;

/**
 * Firma electrónica con el firmador oficial del Ministerio de Hacienda (Manual Técnico §2).
 *
 * El firmador es un servicio LOCAL del contribuyente (Docker o servicio de Windows, puerto 8113) que guarda su
 * certificado; Stockia solo le entrega el documento y la contraseña de la llave privada. Contrato:
 *   POST {firmador}/firmardocumento/   {nit, activo:true, passwordPri, dteJson:{…}}
 *   → HTTP 200 siempre; {status:"OK", body:"<JWS>"} o {status:"ERROR", body:{codigo, mensaje:[…]}}.
 * Sirve igual para DTE y para eventos. El parámetro $sale solo existe por la interfaz común.
 */
class RealSigner implements SignerInterface
{
    public function sign(array $dte, Sale $sale, DteSetting $settings): array
    {
        if (! $settings->signer_password) {
            throw new \RuntimeException('Configure la contraseña de la llave privada del firmador en DTE > Administración (modo real).');
        }

        $nit = (string) ($dte['emisor']['nit'] ?? '');
        if ($nit === '') {
            throw new \RuntimeException('El documento no trae el NIT del emisor: no se puede firmar.');
        }

        $url = MhEndpoints::firmador($settings);

        try {
            $resp = Http::timeout(20)->acceptJson()->asJson()->post($url, [
                'nit' => $nit,
                'activo' => true,
                'passwordPri' => $settings->signer_password,
                'dteJson' => $dte,
            ]);
        } catch (ConnectionException $e) {
            throw new \RuntimeException("El firmador no responde en {$url}. Verifique que el servicio esté en marcha (GET …/firmardocumento/status).");
        }

        $json = $resp->json();
        if (! is_array($json)) {
            throw new \RuntimeException('El firmador devolvió una respuesta que no es JSON (HTTP '.$resp->status().').');
        }

        if (strtoupper((string) ($json['status'] ?? '')) !== 'OK' || ! is_string($json['body'] ?? null) || $json['body'] === '') {
            $b = $json['body'] ?? [];
            $codigo = is_array($b) ? ($b['codigo'] ?? null) : null;
            $mensaje = is_array($b) ? implode(' | ', (array) ($b['mensaje'] ?? [])) : (string) $b;
            throw new \RuntimeException('El firmador rechazó el documento'.($codigo ? " [{$codigo}]" : '').($mensaje !== '' ? ": {$mensaje}" : '').'.');
        }

        return [
            'jsonFirmado' => $json['body'], // JWS compacto: es lo que se transmite a Hacienda
            'payload' => $dte,
            'codigoGeneracion' => $dte['identificacion']['codigoGeneracion'] ?? null,
            'fechaFirma' => now()->toDateTimeString(),
        ];
    }
}
