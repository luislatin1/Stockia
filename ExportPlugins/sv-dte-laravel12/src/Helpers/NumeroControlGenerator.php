<?php
namespace TuEmpresa\SvDte\Helpers;

class NumeroControlGenerator
{
    public static function generar(string $tipoDte, string $establecimiento, string $puntoVenta, int $secuencia): string
    {
        return sprintf("DTE-%s-%s-%s-%015d", $tipoDte, $establecimiento, $puntoVenta, $secuencia);
    }
}