<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Completa dte_distritos.municipio_codigo (departamento + municipio CAT-013, 4 caracteres) con la
 * relación distrito → municipio del Decreto 762. CAT-008 no la trae. Con ella,
 * UbicacionCatalogo::distritoValido() puede exigir que la tripleta sea coherente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $path = dirname(__DIR__).'/data/cat_008_distrito_municipio.json';
        if (! File::exists($path) || ! Schema::hasTable('dte_distritos')) {
            return;
        }

        $mapa = json_decode(File::get($path), true)['mapa'] ?? [];

        foreach ($mapa as $departamento => $distritos) {
            foreach ($distritos as $distrito => $municipio) {
                DB::table('dte_distritos')
                    ->where('departamento_codigo', (string) $departamento)
                    ->where('codigo', (string) $distrito)
                    ->update(['municipio_codigo' => $departamento.$municipio]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dte_distritos')) {
            DB::table('dte_distritos')->update(['municipio_codigo' => null]);
        }
    }
};
