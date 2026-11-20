<?php

namespace Stockia\DteSvMh\Services\Simulation;

use Illuminate\Support\Facades\Cache;
use Stockia\DteSvMh\Models\Dte;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\Contracts\ConsultaInterface;
use Stockia\DteSvMh\Services\Contracts\EventosInterface;
use Stockia\DteSvMh\Services\Contracts\LoteInterface;

/**
 * Simulación de consulta, Evento de Contingencia y lotes. Reutiliza el simulador de recepción
 * (FakeSendService): lo que ese acepte o rechace (incluido `static_estado` de la configuración) rige aquí.
 * El resultado de un lote se guarda en caché para que `consultarLote` lo devuelva, como lo haría Hacienda.
 */
class FakeTransmisionService implements ConsultaInterface, EventosInterface, LoteInterface
{
    public function consultarDte(DteSetting $settings, string $nitEmisor, string $tipoDte, string $codigoGeneracion): array
    {
        $dte = Dte::withoutGlobalScopes()->where('codigo_generacion', $codigoGeneracion)->where('estado', 'ACEPTADO')->first();

        return $dte && $dte->sello_recepcion
            ? ['status' => 'ACEPTADO', 'sello' => $dte->sello_recepcion, 'response' => $dte->respuesta_hacienda, 'error' => null, 'observaciones' => []]
            : ['status' => 'RECHAZADO', 'sello' => null, 'response' => null, 'error' => 'No existe un registro con ese código de generación', 'observaciones' => []];
    }

    public function enviarContingencia(array $firmado, DteSetting $settings): array
    {
        return (new FakeSendService())->send($firmado, $settings);
    }

    public function enviarLote(array $firmados, DteSetting $settings, string $nitEmisor, string $idEnvio): array
    {
        if ($firmados === [] || count($firmados) > self::MAXIMO_POR_LOTE) {
            throw new \RuntimeException('Un lote admite de 1 a '.self::MAXIMO_POR_LOTE.' documentos.');
        }

        $sender = new FakeSendService();
        $resultados = [];
        foreach ($firmados as $f) {
            $codigo = (string) ($f['payload']['identificacion']['codigoGeneracion'] ?? '');
            $resultados[$codigo] = $sender->send($f, $settings);
        }

        $codigoLote = strtoupper((string) \Illuminate\Support\Str::uuid());
        Cache::put("dte-lote-simulado:{$codigoLote}", $resultados, now()->addHours(6));

        return ['recibido' => true, 'codigoLote' => $codigoLote, 'response' => ['estado' => 'RECIBIDO', 'codigoLote' => $codigoLote], 'error' => null];
    }

    public function consultarLote(DteSetting $settings, string $codigoLote): array
    {
        $resultados = Cache::get("dte-lote-simulado:{$codigoLote}", []);
        $procesados = [];
        $rechazados = [];
        foreach ($resultados as $codigo => $r) {
            if (($r['status'] ?? null) === 'ACEPTADO' && ! empty($r['sello'])) {
                $procesados[$codigo] = (string) $r['sello'];
            } else {
                $rechazados[$codigo] = (string) ($r['error'] ?? 'Rechazado en simulación');
            }
        }

        return ['procesados' => $procesados, 'rechazados' => $rechazados, 'response' => ['simulado' => true]];
    }
}
