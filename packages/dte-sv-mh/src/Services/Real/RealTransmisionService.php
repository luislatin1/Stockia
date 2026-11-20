<?php

namespace Stockia\DteSvMh\Services\Real;

use Illuminate\Http\Client\ConnectionException;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\Contracts\AuthInterface;
use Stockia\DteSvMh\Services\Contracts\ConsultaInterface;
use Stockia\DteSvMh\Services\Contracts\EventosInterface;
use Stockia\DteSvMh\Services\Contracts\LoteInterface;

/**
 * Servicios de consulta, Evento de Contingencia y lotes del Sistema de Transmisión (Manual Técnico §4.2.2, §4.3, §4.4).
 */
class RealTransmisionService implements ConsultaInterface, EventosInterface, LoteInterface
{
    use HablaConHacienda;

    public function __construct(private readonly AuthInterface $authService)
    {
    }

    /** POST /fesv/recepcion/consultadte {nitEmisor, tdte, codigoGeneracion}: misma forma de respuesta que la recepción. */
    public function consultarDte(DteSetting $settings, string $nitEmisor, string $tipoDte, string $codigoGeneracion): array
    {
        $resp = $this->conectar(fn () => $this->llamar($settings, 'POST', MhEndpoints::consulta($settings), [
            'nitEmisor' => $this->soloDigitos($nitEmisor),
            'tdte' => $tipoDte,
            'codigoGeneracion' => $codigoGeneracion,
        ], 15));

        if ($resp->serverError()) {
            throw new MhSinRespuestaException('HTTP '.$resp->status());
        }

        return MhRespuestas::documento($resp->json(), $resp->status());
    }

    /**
     * POST /fesv/contingencia {nit, documento:<evento firmado>} → {estado:"RECIBIDO", selloRecibido, mensaje, observaciones}
     * o {estado:"RECHAZADO", mensaje:"Validaciones no superadas", observaciones:[…]}.
     */
    public function enviarContingencia(array $firmado, DteSetting $settings): array
    {
        $jws = (string) ($firmado['jsonFirmado'] ?? '');
        if ($jws === '') {
            throw new \RuntimeException('El evento de contingencia no está firmado.');
        }
        $nit = $this->soloDigitos($firmado['payload']['emisor']['nit'] ?? '');

        $resp = $this->conectar(fn () => $this->llamar($settings, 'POST', MhEndpoints::contingencia($settings), ['nit' => $nit, 'documento' => $jws], 20));
        $json = $resp->json() ?: [];

        $aceptado = strtoupper((string) ($json['estado'] ?? '')) === 'RECIBIDO' && ! empty($json['selloRecibido']);

        return [
            'status' => $aceptado ? 'ACEPTADO' : 'RECHAZADO',
            'sello' => $aceptado ? (string) $json['selloRecibido'] : null,
            'response' => $json ?: null,
            'error' => $aceptado ? null : trim(($json['mensaje'] ?? 'Evento rechazado').' '.implode(' | ', (array) ($json['observaciones'] ?? []))),
        ];
    }

    /**
     * POST /fesv/recepcionlote {ambiente, idEnvio:UUID, version:1, nitEmisor, documentos:[JWS…]} (máx. 100)
     * → {estado:"RECIBIDO", codigoLote}. Hacienda procesa en minutos: el resultado se consulta con el código de lote.
     */
    public function enviarLote(array $firmados, DteSetting $settings, string $nitEmisor, string $idEnvio): array
    {
        if ($firmados === [] || count($firmados) > self::MAXIMO_POR_LOTE) {
            throw new \RuntimeException('Un lote admite de 1 a '.self::MAXIMO_POR_LOTE.' documentos.');
        }

        $documentos = array_map(function ($f) {
            $jws = (string) ($f['jsonFirmado'] ?? '');
            if ($jws === '') {
                throw new \RuntimeException('Hay un documento sin firmar en el lote.');
            }

            return $jws;
        }, array_values($firmados));

        $resp = $this->conectar(fn () => $this->llamar($settings, 'POST', MhEndpoints::lote($settings), [
            'ambiente' => (string) $settings->ambiente,
            'idEnvio' => strtoupper($idEnvio),
            'version' => 1,
            'nitEmisor' => $this->soloDigitos($nitEmisor),
            'documentos' => $documentos,
        ], 30));
        $json = $resp->json() ?: [];

        $recibido = strtoupper((string) ($json['estado'] ?? '')) === 'RECIBIDO' && ! empty($json['codigoLote']);

        return [
            'recibido' => $recibido,
            'codigoLote' => $recibido ? (string) $json['codigoLote'] : null,
            'response' => $json ?: null,
            'error' => $recibido ? null : MhRespuestas::mensajeDeRechazo($json, $resp->status()),
        ];
    }

    /** GET /fesv/recepcion/consultadtelote/{codigoLote} → {procesados:[…], rechazados:[…]} con el resultado por documento. */
    public function consultarLote(DteSetting $settings, string $codigoLote): array
    {
        $resp = $this->conectar(fn () => $this->llamar($settings, 'GET', MhEndpoints::consultaLote($settings, $codigoLote), null, 20));
        $json = $resp->json() ?: [];

        $procesados = [];
        foreach ((array) ($json['procesados'] ?? []) as $d) {
            if (! empty($d['codigoGeneracion']) && ! empty($d['selloRecibido'])) {
                $procesados[(string) $d['codigoGeneracion']] = (string) $d['selloRecibido'];
            }
        }
        $rechazados = [];
        foreach ((array) ($json['rechazados'] ?? []) as $d) {
            if (! empty($d['codigoGeneracion'])) {
                $rechazados[(string) $d['codigoGeneracion']] = MhRespuestas::mensajeDeRechazo($d, 200);
            }
        }

        return ['procesados' => $procesados, 'rechazados' => $rechazados, 'response' => $json ?: null];
    }

    /** Convierte una caída de conexión en un error legible, sin exponer detalles internos. */
    private function conectar(callable $llamada)
    {
        try {
            return $llamada();
        } catch (ConnectionException $e) {
            throw new MhSinRespuestaException('No hay respuesta del servicio de Hacienda (tiempo de espera o conexión).');
        }
    }
}
