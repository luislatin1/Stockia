<?php

namespace App\Validators;

use Exception;

class ValidadorCoherenciaDte
{
    public static function validar(string $tipoDte, string $tipoDocumento): bool
    {
        $reglas = [
            '01' => ['13', '36'], // Factura
            '03' => ['36'], // CCF
            '05' => ['13', '36'], // Nota Crédito
            '06' => ['13', '36'], // Nota Débito
        ];

        if (! isset($reglas[$tipoDte])) {
            return true;
        }

        if (! in_array($tipoDocumento, $reglas[$tipoDte], true)) {
            throw new Exception('Tipo de documento no permitido para este tipo de DTE.');
        }

        return true;
    }
}
