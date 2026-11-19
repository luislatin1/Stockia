<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos de ubicación para formularios y validación: departamento (CAT-012),
 * municipio (CAT-013) y distrito (CAT-008).
 *
 * Los códigos de municipio se guardan como departamento+municipio (4 caracteres)
 * porque el código local se repite entre departamentos; al emitir el DTE se
 * envían los 2 últimos. El distrito se guarda con su código de 2 caracteres, único
 * solo dentro del departamento.
 */
class UbicacionCatalogo
{
    public static function departamentos(): Collection
    {
        if (! Schema::hasTable('dte_departamentos')) {
            return collect();
        }

        return DB::table('dte_departamentos')->where('activo', true)->orderBy('codigo')->get(['codigo', 'nombre']);
    }

    public static function municipios(): Collection
    {
        if (! Schema::hasTable('dte_municipios')) {
            return collect();
        }

        return DB::table('dte_municipios')->where('activo', true)->orderBy('codigo')
            ->get(['codigo', 'departamento_codigo', 'nombre'])
            ->map(function ($row) {
                $row->codigo_local = substr((string) $row->codigo, -2);

                return $row;
            });
    }

    public static function distritos(): Collection
    {
        if (! Schema::hasTable('dte_distritos')) {
            return collect();
        }

        return DB::table('dte_distritos')->where('activo', true)
            ->orderBy('departamento_codigo')->orderBy('nombre')
            ->get(['departamento_codigo', 'municipio_codigo', 'codigo', 'nombre']);
    }

    /**
     * ¿El distrito existe dentro de ese departamento? Si ya se conoce el municipio
     * de cada distrito, también se exige que coincida (Hacienda valida la tripleta).
     */
    public static function distritoValido(?string $departamento, ?string $municipio, ?string $distrito): bool
    {
        if ($distrito === null || $distrito === '') {
            return true;
        }

        if (! Schema::hasTable('dte_distritos')) {
            return true;
        }

        $row = DB::table('dte_distritos')
            ->where('departamento_codigo', (string) $departamento)
            ->where('codigo', $distrito)
            ->first();

        if (! $row) {
            return false;
        }

        if ($row->municipio_codigo === null) {
            return true;
        }

        // Los clientes guardan el municipio con 2 caracteres; las empresas, con 4.
        $municipio = (string) $municipio;
        if (strlen($municipio) === 2) {
            $municipio = (string) $departamento.$municipio;
        }

        return $row->municipio_codigo === $municipio;
    }
}
