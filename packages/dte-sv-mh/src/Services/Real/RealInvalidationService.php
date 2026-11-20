<?php

namespace Stockia\DteSvMh\Services\Real;

use App\Models\Company;
use App\Models\Sale;
use Illuminate\Http\Client\ConnectionException;
use Stockia\DteSvMh\Models\Dte;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\Contracts\AuthInterface;
use Stockia\DteSvMh\Services\Contracts\InvalidationInterface;
use Stockia\DteSvMh\Services\Contracts\SignerInterface;

/**
 * Evento de Invalidación (Manual Técnico §4.5): el evento se FIRMA con el firmador y se transmite a
 *   POST /fesv/anulardte {ambiente, idEnvio:int, version:3, documento:<evento firmado>}
 *   → 200 {estado:"PROCESADO", selloRecibido, …}  |  {estado:"RECHAZADO", codigoMsg, descripcionMsg, observaciones}
 * Si Hacienda no lo acepta se lanza una excepción: el documento NO debe quedar marcado como invalidado.
 */
class RealInvalidationService implements InvalidationInterface
{
    use HablaConHacienda;

    public function __construct(
        private readonly AuthInterface $authService,
        private readonly SignerInterface $signer,
    ) {
    }

    public function invalidate(Dte $dte, DteSetting $settings, array $payload = []): array
    {
        $evento = $payload['evento'] ?? null;
        if (! is_array($evento)) {
            throw new \RuntimeException('Falta el evento de invalidación para transmitir.');
        }

        $company = Company::withoutGlobalScopes()->find($dte->company_id);
        $contexto = new Sale();
        $contexto->company_id = $dte->company_id;
        $contexto->setRelation('company', $company);

        $firmado = $this->signer->sign($evento, $contexto, $settings);

        try {
            $resp = $this->llamar($settings, 'POST', MhEndpoints::anular($settings), [
                'ambiente' => (string) $settings->ambiente,
                'idEnvio' => (int) $dte->id, // correlativo a discreción del emisor
                'version' => (int) ($evento['identificacion']['version'] ?? 3),
                'documento' => $firmado['jsonFirmado'],
            ], 20);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('Hacienda no respondió a la invalidación: el documento sigue vigente. Reintente.');
        }

        $json = $resp->json() ?: [];
        if (strtoupper((string) ($json['estado'] ?? '')) === 'PROCESADO' && ! empty($json['selloRecibido'])) {
            return [
                'estado' => 'INVALIDADO',
                'codigoGeneracion' => $dte->codigo_generacion,
                'selloRecibido' => (string) $json['selloRecibido'],
                'fhProcesamiento' => $json['fhProcesamiento'] ?? null,
                'observaciones' => (array) ($json['observaciones'] ?? []),
                'respuestaMH' => $json,
            ];
        }

        throw new \RuntimeException('Hacienda rechazó la invalidación: '.MhRespuestas::mensajeDeRechazo($json, $resp->status()));
    }
}
