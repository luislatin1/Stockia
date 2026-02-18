<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DteCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalogBaseDirs = [
            base_path('ExportPlugins/sv-dte-laravel12/catalogos-dte-acreditacion-completo-2026/json'),
            database_path('data/dte'),
        ];

        $catalogMap = [
            'cat_014_unidades_medida.json' => ['table' => 'dte_cat_014_unidades_medida', 'numeric_code' => true],
            'cat_022_tipo_documento.json' => ['table' => 'dte_cat_022_tipos_documento', 'numeric_code' => false],
            'cat_024_tipo_invalidacion.json' => ['table' => 'dte_cat_024_tipos_invalidacion', 'numeric_code' => false],
            'cat_tipos_dte.json' => ['table' => 'dte_cat_tipos_dte', 'numeric_code' => false],
            'cat_condicion_operacion.json' => ['table' => 'dte_cat_condicion_operacion', 'numeric_code' => false],
            'cat_tipo_modelo.json' => ['table' => 'dte_cat_tipo_modelo', 'numeric_code' => false],
            'cat_tipo_operacion.json' => ['table' => 'dte_cat_tipo_operacion', 'numeric_code' => false],
            'cat_tipo_item.json' => ['table' => 'dte_cat_tipo_item', 'numeric_code' => false],
            'cat_tipo_establecimiento.json' => ['table' => 'dte_cat_tipo_establecimiento', 'numeric_code' => false],
            'cat_tributos.json' => ['table' => 'dte_tributos', 'numeric_code' => false],
        ];

        foreach ($catalogMap as $fileName => $cfg) {
            if (! Schema::hasTable($cfg['table'])) {
                continue;
            }

            $path = collect($catalogBaseDirs)
                ->map(fn ($dir) => rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName)
                ->first(fn ($candidate) => File::exists($candidate));

            if (! $path) {
                continue;
            }

            $rows = json_decode(File::get($path), true);
            if (! is_array($rows)) {
                continue;
            }

            $payload = collect($rows)->map(function ($item) use ($cfg, $fileName) {
                $codigoRaw = (string) ($item['codigo'] ?? '');
                $codigo = $cfg['numeric_code']
                    ? (int) $codigoRaw
                    : str_pad($codigoRaw, 2, '0', STR_PAD_LEFT);

                $row = [
                    'codigo' => $codigo,
                    'descripcion' => (string) ($item['descripcion'] ?? $codigoRaw),
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($cfg['table'] === 'dte_tributos') {
                    $row['tasa'] = match ((string) $codigoRaw) {
                        '20' => 0.13,
                        'C3' => 0.01,
                        default => null,
                    };
                }

                return $row;
            })->filter(fn ($row) => $row['codigo'] !== '' && $row['codigo'] !== 0)->values()->all();

            if (empty($payload)) {
                continue;
            }

            $updateCols = ['descripcion', 'activo', 'updated_at'];
            if ($cfg['table'] === 'dte_tributos') {
                $updateCols[] = 'tasa';
            }

            DB::table($cfg['table'])->upsert($payload, ['codigo'], $updateCols);
        }

        $catalogPaths = [
            base_path('ExportPlugins/sv-dte-laravel12/catalogo-ubicaciones-sv-dte-2026/json/departamentos_municipios_sv_2026.json'),
            database_path('data/dte/sv_departamentos_municipios_2026.json'),
        ];
        $catalogPath = collect($catalogPaths)->first(fn ($path) => File::exists($path));

        if ($catalogPath && File::exists($catalogPath)) {
            $json = json_decode(File::get($catalogPath), true);
            $departamentos = [];
            $municipios = [];

            foreach (($json['departamentos'] ?? []) as $department) {
                $depCode = str_pad((string) ($department['codigo'] ?? ''), 2, '0', STR_PAD_LEFT);
                if ($depCode === '') {
                    continue;
                }

                $departamentos[] = [
                    'codigo' => $depCode,
                    'nombre' => (string) ($department['nombre'] ?? $depCode),
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                foreach (($department['municipios'] ?? []) as $municipality) {
                    $munCode = str_pad((string) ($municipality['codigo'] ?? ''), 2, '0', STR_PAD_LEFT);
                    if ($munCode === '') {
                        continue;
                    }

                    $municipios[] = [
                        'codigo' => $depCode . $munCode,
                        'departamento_codigo' => $depCode,
                        'nombre' => (string) ($municipality['nombre'] ?? ($depCode . '-' . $munCode)),
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (! empty($departamentos)) {
                DB::table('dte_departamentos')->upsert(
                    $departamentos,
                    ['codigo'],
                    ['nombre', 'activo', 'updated_at']
                );
            }

            if (! empty($municipios)) {
                DB::table('dte_municipios')->upsert(
                    $municipios,
                    ['codigo'],
                    ['departamento_codigo', 'nombre', 'activo', 'updated_at']
                );
            }
        } else {
            DB::table('dte_departamentos')->upsert([
                ['codigo' => '01', 'nombre' => 'Ahuachapán', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['codigo' => '02', 'nombre' => 'Santa Ana', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ], ['codigo'], ['nombre', 'activo', 'updated_at']);

            DB::table('dte_municipios')->upsert([
                ['codigo' => '0101', 'departamento_codigo' => '01', 'nombre' => 'Ahuachapán Centro', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['codigo' => '0201', 'departamento_codigo' => '02', 'nombre' => 'Santa Ana Centro', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ], ['codigo'], ['departamento_codigo', 'nombre', 'activo', 'updated_at']);
        }
    }
}
