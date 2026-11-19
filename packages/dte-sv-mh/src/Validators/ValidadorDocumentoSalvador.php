<?php

namespace Stockia\DteSvMh\Validators;

use App\Support\FiscalId;
use Exception;

class ValidadorDocumentoSalvador
{
    public static function validar(string $tipoDocumento, string $numero): bool
    {
        $numero = trim($numero);

        switch ($tipoDocumento) {
            case '13': // DUI: en DTE 2.0 son 9 dígitos; se acepta escrito con o sin guion
                if (FiscalId::duiDigits($numero) === null) {
                    throw new Exception('DUI inválido. Debe tener 9 dígitos (########-#).');
                }
                break;

            case '36': // NIT: 14 dígitos, o 9 (formato antiguo); con o sin guiones
                if (FiscalId::nitDigits($numero) === null) {
                    throw new Exception('NIT inválido. Debe tener 14 dígitos (o 9 en formato antiguo).');
                }
                break;

            case '03': // Pasaporte
                if (! preg_match('/^[A-Za-z0-9]{3,20}$/', $numero)) {
                    throw new Exception('Pasaporte inválido.');
                }
                break;

            case '02': // Carné residente
            case '37': // Otro documento extranjero
                if (strlen($numero) < 3) {
                    throw new Exception('Documento extranjero inválido.');
                }
                break;

            default:
                throw new Exception('Tipo de documento no permitido.');
        }

        return true;
    }

    /** NRC: sin guion ni ceros a la izquierda, de 2 a 8 dígitos (Normativa v2.0, Anexo IV). */
    public static function validarNrc(?string $nrc): bool
    {
        if (FiscalId::nrcDigits($nrc) === null) {
            throw new Exception('NRC inválido. Debe tener de 2 a 8 dígitos (######-#).');
        }

        return true;
    }
}
