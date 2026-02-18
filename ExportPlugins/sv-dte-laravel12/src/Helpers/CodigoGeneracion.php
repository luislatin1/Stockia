<?php
namespace TuEmpresa\SvDte\Helpers;

use Illuminate\Support\Str;

class CodigoGeneracion
{
    public static function generar(): string
    {
        return strtoupper(Str::uuid()->toString());
    }
}