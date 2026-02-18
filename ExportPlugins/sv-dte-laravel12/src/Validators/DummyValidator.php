<?php
namespace TuEmpresa\SvDte\Validators;

class DummyValidator
{
    public static function validate(array $dte): bool
    {
        if (!isset($dte['identificacion'])) {
            throw new \Exception("Identificación requerida");
        }

        if (!isset($dte['emisor']['nit'])) {
            throw new \Exception("NIT emisor requerido");
        }

        if (empty($dte['cuerpoDocumento'])) {
            throw new \Exception("Debe existir al menos un ítem");
        }

        return true;
    }
}