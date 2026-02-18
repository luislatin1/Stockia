<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbicacionesSeeder extends Seeder
{
    public function run(): void
    {
        $json = json_decode(file_get_contents(base_path('json/departamentos_municipios_sv_2026.json')), true);

        foreach ($json['departamentos'] as $dep) {
            DB::table('departamentos')->insert([
                'codigo' => $dep['codigo'],
                'nombre' => $dep['nombre'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            foreach ($dep['municipios'] as $mun) {
                DB::table('municipios')->insert([
                    'codigo' => $mun['codigo'],
                    'departamento_codigo' => $dep['codigo'],
                    'nombre' => $mun['nombre'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
