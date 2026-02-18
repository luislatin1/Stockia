<?php
namespace App\Validators;

use Illuminate\Support\Facades\DB;
use Exception;

class UbicacionValidator
{
    public static function validar(string $departamento, string $municipio): bool
    {
        $existe = DB::table('municipios')
            ->where('codigo', $municipio)
            ->where('departamento_codigo', $departamento)
            ->exists();

        if (!$existe) {
            throw new Exception("Municipio no pertenece al departamento indicado.");
        }

        return true;
    }
}
