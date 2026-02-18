<?php

namespace App\Validators;

use Exception;

class ValidadorDocumentoSalvador
{
    public static function validar(string $tipoDocumento, string $numero): bool
    {
        $numero = trim($numero);

        switch ($tipoDocumento) {
            case '13': // DUI
                if (! preg_match('/^\d{8}-\d{1}$/', $numero)) {
                    throw new Exception('Formato DUI inválido. Debe ser ########-#');
                }
                break;

            case '36': // NIT
                $limpio = preg_replace('/[^0-9]/', '', $numero);
                if (strlen((string) $limpio) !== 14) {
                    throw new Exception('NIT inválido. Debe contener 14 dígitos.');
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
}
