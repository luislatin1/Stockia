<?php

namespace Stockia\DteSvMh\Services\Real;

use Stockia\DteSvMh\Models\DteSetting;

/**
 * URLs del Sistema de Transmisión (Manual Técnico de Integración v2.0, §4 y §6.2).
 *
 *  - Pruebas:    https://apitest.dtes.mh.gob.sv
 *  - Producción: https://api.dtes.mh.gob.sv
 * Las rutas deben usarse EXACTAS y sin barra final (§6.2). `auth_url` y `send_url` de la configuración, si se
 * llenan, mandan sobre las predeterminadas (p. ej. un proxy); el resto se deriva de su servidor.
 *
 * Separación de ambientes (§6.3): nunca se envía un documento de ambiente 00 a producción ni uno de 01 a pruebas.
 */
final class MhEndpoints
{
    public const PRUEBAS = 'https://apitest.dtes.mh.gob.sv';

    public const PRODUCCION = 'https://api.dtes.mh.gob.sv';

    public const FIRMADOR_DEFECTO = 'http://127.0.0.1:8113/firmardocumento/';

    public const USER_AGENT = 'Stockia-DTE/1.0';

    public static function base(DteSetting $s): string
    {
        $explicita = self::servidorDe($s->send_url) ?: self::servidorDe($s->auth_url);
        $base = $explicita ?: ((string) $s->ambiente === '01' ? self::PRODUCCION : self::PRUEBAS);

        self::verificarAmbiente($s, $base);

        return $base;
    }

    public static function auth(DteSetting $s): string
    {
        $url = self::limpiar($s->auth_url) ?: self::base($s).'/seguridad/auth';
        self::verificarAmbiente($s, $url);

        return $url;
    }

    public static function recepcion(DteSetting $s): string
    {
        $url = self::limpiar($s->send_url) ?: self::base($s).'/fesv/recepciondte';
        self::verificarAmbiente($s, $url);

        return $url;
    }

    public static function lote(DteSetting $s): string
    {
        return self::base($s).'/fesv/recepcionlote';
    }

    public static function consulta(DteSetting $s): string
    {
        return self::base($s).'/fesv/recepcion/consultadte';
    }

    public static function consultaLote(DteSetting $s, string $codigoLote): string
    {
        return self::base($s).'/fesv/recepcion/consultadtelote/'.rawurlencode($codigoLote);
    }

    public static function contingencia(DteSetting $s): string
    {
        return self::base($s).'/fesv/contingencia';
    }

    public static function anular(DteSetting $s): string
    {
        return self::base($s).'/fesv/anulardte';
    }

    /** El firmador es un servicio local del contribuyente; su ruta oficial SÍ lleva barra final (`/firmardocumento/`). */
    public static function firmador(DteSetting $s): string
    {
        $url = trim((string) $s->signer_url);

        return rtrim($url !== '' ? $url : self::FIRMADOR_DEFECTO, '/').'/';
    }

    public static function limpiar(?string $url): ?string
    {
        $url = trim((string) $url);

        return $url === '' ? null : rtrim($url, '/');
    }

    private static function servidorDe(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        $p = parse_url($url);
        if (empty($p['scheme']) || empty($p['host'])) {
            return null;
        }

        return $p['scheme'].'://'.$p['host'].(isset($p['port']) ? ':'.$p['port'] : '');
    }

    private static function verificarAmbiente(DteSetting $s, string $url): void
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $ambiente = (string) $s->ambiente;

        if ($ambiente === '00' && $host === 'api.dtes.mh.gob.sv') {
            throw new \RuntimeException('Ambiente de PRUEBAS (00) apuntando al servidor de PRODUCCIÓN: se bloquea para no enviar documentos de prueba a producción.');
        }
        if ($ambiente === '01' && $host === 'apitest.dtes.mh.gob.sv') {
            throw new \RuntimeException('Ambiente de PRODUCCIÓN (01) apuntando al servidor de PRUEBAS: corrija la URL o el ambiente.');
        }
    }
}
