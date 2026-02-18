<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosDteAcreditacionSeeder extends Seeder
{
    public function run(): void
    {
        $catalogos = [
            'cat_014_unidades_medida.json' => 'catalogo_unidades_medida',
            'cat_022_tipo_documento.json' => 'catalogo_tipo_documento',
            'cat_024_tipo_invalidacion.json' => 'catalogo_tipo_invalidacion',
            'cat_tipos_dte.json' => 'catalogo_tipos_dte',
            'cat_condicion_operacion.json' => 'catalogo_condicion_operacion',
            'cat_tipo_modelo.json' => 'catalogo_tipo_modelo',
            'cat_tipo_operacion.json' => 'catalogo_tipo_operacion',
            'cat_tipo_item.json' => 'catalogo_tipo_item',
            'cat_tipo_establecimiento.json' => 'catalogo_tipo_establecimiento',
            'cat_tributos.json' => 'catalogo_tributos'
        ];

        foreach ($catalogos as $file => $tabla) {
            $data = json_decode(file_get_contents(base_path('json/'.$file)), true);
            foreach ($data as $item) {
                DB::table($tabla)->insert([
                    'codigo' => $item['codigo'],
                    'descripcion' => $item['descripcion'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
