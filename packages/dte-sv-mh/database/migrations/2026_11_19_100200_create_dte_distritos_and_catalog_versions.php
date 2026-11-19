<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-008 Distrito (vigente en DTE 2.0) y registro de versiones de catálogo.
 *
 * LIMITACIÓN CONOCIDA: el catálogo oficial no indica a qué municipio pertenece
 * cada distrito; solo viene agrupado por departamento. Hacienda valida que el
 * distrito "tenga correspondencia con departamento y municipio", así que
 * `municipio_codigo` queda NULL hasta conseguir esa jerarquía de una fuente
 * oficial. Mientras tanto la interfaz filtra por departamento.
 * Ver FASE0_HALLAZGOS.md §4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dte_distritos', function (Blueprint $table) {
            $table->id();
            $table->string('departamento_codigo', 2);   // CAT-012
            $table->string('municipio_codigo', 4)->nullable(); // dep+mun (dte_municipios.codigo), pendiente
            $table->string('codigo', 2);                // CAT-008, único solo dentro del departamento
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['departamento_codigo', 'codigo']);
        });

        Schema::create('dte_catalogo_versiones', function (Blueprint $table) {
            $table->id();
            $table->string('catalogo', 10);             // CAT-008
            $table->string('version', 20);              // versión del documento fuente
            $table->string('fuente');                   // archivo / URL de origen
            $table->unsignedInteger('filas')->default(0);
            $table->string('hash', 64)->nullable();     // sha256 del archivo fuente
            $table->date('vigente_desde')->nullable();
            $table->text('nota')->nullable();
            $table->timestamp('cargado_en');
            $table->timestamps();

            $table->index(['catalogo', 'cargado_en']);
        });

        $this->cargarDistritos();
    }

    private function cargarDistritos(): void
    {
        $path = dirname(__DIR__).'/data/cat_008_distritos.json';
        if (! File::exists($path)) {
            return;
        }

        $json = json_decode(File::get($path), true);
        $now = now();
        $rows = [];

        foreach (($json['departamentos'] ?? []) as $departamento => $distritos) {
            foreach ($distritos as $distrito) {
                $rows[] = [
                    'departamento_codigo' => (string) $departamento,
                    'municipio_codigo' => null,
                    'codigo' => (string) $distrito['codigo'],
                    'nombre' => (string) $distrito['nombre'],
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('dte_distritos')->insert($chunk);
        }

        DB::table('dte_catalogo_versiones')->insert([
            'catalogo' => 'CAT-008',
            'version' => (string) ($json['version'] ?? '1.1'),
            'fuente' => (string) ($json['fuente'] ?? basename($path)),
            'filas' => count($rows),
            'hash' => hash_file('sha256', $path),
            'vigente_desde' => null,
            'nota' => (string) ($json['nota'] ?? ''),
            'cargado_en' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_catalogo_versiones');
        Schema::dropIfExists('dte_distritos');
    }
};
