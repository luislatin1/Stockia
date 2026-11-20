<?php

namespace Stockia\DteSvMh\Services\Real;

use Illuminate\Http\Client\ConnectionException;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\Contracts\AuthInterface;
use Stockia\DteSvMh\Services\Contracts\ConsultaInterface;
use Stockia\DteSvMh\Services\Contracts\SendInterface;

/**
 * Recepción uno a uno (Manual Técnico §4.2.1) con la política de reintentos de §3.3.
 *
 *   POST {servidor}/fesv/recepciondte
 *   {ambiente, idEnvio:int, version:int, tipoDte, documento:<JWS>, codigoGeneracion}
 *   → 200 {estado:"PROCESADO", selloRecibido, …}   (aceptado, con o sin observaciones)
 *   → 400 {estado:"RECHAZADO", codigoMsg, descripcionMsg, observaciones}
 *
 * Política de reintentos: si Hacienda no responde en 8 s (o responde 5xx / se corta la conexión) → consultar si el
 * documento llegó → si no llegó, reenviar; como máximo 2 reenvíos. Si persiste, se devuelve SIN_RESPUESTA para que
 * el llamador decida (el documento queda pendiente; pasar a contingencia es decisión de negocio).
 */
class RealSendService implements SendInterface
{
    use HablaConHacienda;

    /** Espera máxima por respuesta (§3.3). */
    public const ESPERA_SEGUNDOS = 8;

    public const REENVIOS_MAXIMOS = 2;

    public function __construct(
        private readonly AuthInterface $authService,
        private readonly ?ConsultaInterface $consulta = null,
    ) {
    }

    public function send(array $signedDte, DteSetting $settings): array
    {
        $jws = (string) ($signedDte['jsonFirmado'] ?? '');
        $doc = $signedDte['payload'] ?? [];
        $ident = $doc['identificacion'] ?? [];
        if ($jws === '' || empty($ident['codigoGeneracion'])) {
            throw new \RuntimeException('El documento no está firmado: firme antes de transmitir.');
        }

        // Los eventos propios (p. ej. Retorno, tipoEvento 18) no traen tipoDte: su código está en tipoEvento.
        $tipoDte = (string) ($ident['tipoDte'] ?? $ident['tipoEvento'] ?? '');
        $codigo = (string) $ident['codigoGeneracion'];
        $nit = $this->soloDigitos($doc['emisor']['nit'] ?? '');
        $url = MhEndpoints::recepcion($settings);

        $cuerpo = [
            'ambiente' => (string) $settings->ambiente,
            'idEnvio' => (int) ($signedDte['id_envio'] ?? 1), // correlativo a discreción del emisor
            'version' => (int) ($ident['version'] ?? 1),
            'tipoDte' => $tipoDte,
            'documento' => $jws,
            'codigoGeneracion' => $codigo,
        ];

        $motivo = 'sin detalle';
        for ($intento = 0; $intento <= self::REENVIOS_MAXIMOS; $intento++) {
            try {
                $resp = $this->llamar($settings, 'POST', $url, $cuerpo, self::ESPERA_SEGUNDOS);
                if ($resp->serverError()) {
                    throw new MhSinRespuestaException('HTTP '.$resp->status());
                }

                return MhRespuestas::documento($resp->json(), $resp->status());
            } catch (ConnectionException|MhSinRespuestaException $e) {
                $motivo = $e->getMessage();

                // ¿Llegó a pesar de no haber recibido la respuesta? Entonces no se reenvía (se evitaría el duplicado).
                $previo = $this->yaRecibido($settings, $nit, $tipoDte, $codigo);
                if ($previo !== null) {
                    return $previo;
                }
            }
        }

        return [
            'status' => 'SIN_RESPUESTA',
            'sello' => null,
            'response' => null,
            'error' => 'Hacienda no respondió tras '.(self::REENVIOS_MAXIMOS + 1)." intentos ({$motivo}). El documento queda pendiente de transmisión.",
            'observaciones' => [],
        ];
    }

    /** Consulta si el documento ya fue recibido; null si no lo fue o si la consulta misma falla. */
    private function yaRecibido(DteSetting $settings, string $nit, string $tipoDte, string $codigo): ?array
    {
        try {
            $r = ($this->consulta ?? new RealTransmisionService($this->authService))->consultarDte($settings, $nit, $tipoDte, $codigo);
        } catch (\Throwable) {
            return null;
        }

        return $r['status'] === 'ACEPTADO' ? $r : null;
    }
}
