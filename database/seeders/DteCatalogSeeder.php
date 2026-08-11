<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DteCatalogSeeder extends Seeder
{
    /**
     * Mapa Catálogo MH -> tabla destino.
     * Fuente: ExportPlugins/DTE_catalogos_confirmados/CAT-0NN.json
     * (CAT-008 está eliminado, CAT-019 y CAT-020 se manejan en seeders/tablas aparte).
     */
    private const CATALOG_TABLE_MAP = [
        'CAT-001' => 'dte_cat_001_ambiente',
        'CAT-002' => 'dte_cat_tipos_dte',
        'CAT-003' => 'dte_cat_tipo_modelo',
        'CAT-004' => 'dte_cat_tipo_operacion',
        'CAT-005' => 'dte_cat_005_tipo_contingencia',
        'CAT-006' => 'dte_cat_006_retencion_iva',
        'CAT-007' => 'dte_cat_007_tipo_generacion',
        'CAT-009' => 'dte_cat_tipo_establecimiento',
        'CAT-010' => 'dte_cat_010_tipo_servicio_medico',
        'CAT-011' => 'dte_cat_tipo_item',
        'CAT-014' => 'dte_cat_014_unidades_medida',
        'CAT-015' => 'dte_tributos',
        'CAT-016' => 'dte_cat_condicion_operacion',
        'CAT-017' => 'dte_cat_017_formas_pago',
        'CAT-018' => 'dte_cat_018_plazo',
        'CAT-021' => 'dte_cat_021_otros_documentos',
        'CAT-022' => 'dte_cat_022_tipos_documento',
        'CAT-023' => 'dte_cat_023_tipo_doc_contingencia',
        'CAT-024' => 'dte_cat_024_tipos_invalidacion',
        'CAT-025' => 'dte_cat_025_titulo_transporte',
        'CAT-026' => 'dte_cat_026_tipo_donacion',
        'CAT-027' => 'dte_cat_027_recinto_fiscal',
        'CAT-028' => 'dte_cat_028_regimen',
        'CAT-029' => 'dte_cat_029_tipo_persona',
        'CAT-030' => 'dte_cat_030_transporte',
        'CAT-031' => 'dte_cat_031_incoterms',
        'CAT-032' => 'dte_cat_032_domicilio_fiscal',
    ];

    /** CAT-014 (Unidad de Medida) usa columna entera, no string. */
    private const NUMERIC_CODE_TABLES = [
        'dte_cat_014_unidades_medida',
    ];

    private const ACCENT_MAP = [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
    ];

    public function run(): void
    {
        $this->seedConfirmedCatalogs();
        $this->seedGeography();
    }

    private function catalogDir(): string
    {
        return base_path('ExportPlugins/DTE_catalogos_confirmados');
    }

    private function readCatalog(string $catCode): ?array
    {
        $path = $this->catalogDir() . DIRECTORY_SEPARATOR . $catCode . '.json';

        if (! File::exists($path)) {
            return null;
        }

        $json = json_decode(File::get($path), true);

        return is_array($json['datos'] ?? null) ? $json['datos'] : null;
    }

    private function seedConfirmedCatalogs(): void
    {
        foreach (self::CATALOG_TABLE_MAP as $catCode => $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $rows = $this->readCatalog($catCode);
            if (! $rows) {
                continue;
            }

            $numeric = in_array($table, self::NUMERIC_CODE_TABLES, true);

            $payload = collect($rows)->map(function ($item) use ($table, $numeric) {
                $codigoRaw = trim((string) ($item['codigo'] ?? ''));
                $descripcion = trim((string) ($item['descripcion'] ?? $codigoRaw));

                $row = [
                    'codigo' => $numeric ? (int) $codigoRaw : $codigoRaw,
                    'descripcion' => $descripcion,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($table === 'dte_tributos') {
                    $row['tasa'] = $this->extractSingleRate($descripcion);
                }

                return $row;
            })->filter(fn ($row) => $row['codigo'] !== '' && $row['codigo'] !== 0)->values()->all();

            if (empty($payload)) {
                continue;
            }

            $updateCols = ['descripcion', 'activo', 'updated_at'];
            if ($table === 'dte_tributos') {
                $updateCols[] = 'tasa';
            }

            DB::table($table)->upsert($payload, ['codigo'], $updateCols);

            $officialCodes = collect($payload)->pluck('codigo')->all();
            DB::table($table)->whereNotIn('codigo', $officialCodes)->delete();
        }
    }

    /**
     * Extrae una tasa porcentual únicamente cuando la descripción trae un solo
     * porcentaje inequívoco (ej. "13%"). Descripciones con varias tasas posibles
     * (ej. "0%, 0.5%, 1%") se dejan en null porque dependen del caso concreto.
     */
    private function extractSingleRate(string $descripcion): ?float
    {
        $matches = [];
        preg_match_all('/(\d+(?:\.\d+)?)\s*%/', $descripcion, $matches);

        if (count($matches[1]) !== 1) {
            return null;
        }

        return round(((float) $matches[1][0]) / 100, 4);
    }

    private function stripAccents(string $value): string
    {
        return strtr($value, self::ACCENT_MAP);
    }

    private function seedGeography(): void
    {
        $departamentosCat = $this->readCatalog('CAT-012');
        $municipiosCat = $this->readCatalog('CAT-013');

        if (! $departamentosCat || ! $municipiosCat) {
            return;
        }

        $departamentos = [];
        $deptCodeByName = [];

        foreach ($departamentosCat as $row) {
            $codigo = str_pad(trim((string) ($row['codigo'] ?? '')), 2, '0', STR_PAD_LEFT);
            if ($codigo === '') {
                continue;
            }

            $nombre = trim((string) ($row['descripcion'] ?? $codigo));

            $departamentos[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($codigo !== '00') {
                $deptCodeByName[mb_strtoupper($this->stripAccents($nombre))] = $codigo;
            }
        }

        if (! empty($departamentos)) {
            DB::table('dte_departamentos')->upsert(
                $departamentos,
                ['codigo'],
                ['nombre', 'activo', 'updated_at']
            );

            $officialDeptCodes = collect($departamentos)->pluck('codigo')->all();
            DB::table('dte_departamentos')->whereNotIn('codigo', $officialDeptCodes)->delete();
        }

        $municipios = [];

        foreach ($municipiosCat as $row) {
            $munCodigo = str_pad(trim((string) ($row['codigo'] ?? '')), 2, '0', STR_PAD_LEFT);
            $descripcion = trim((string) ($row['descripcion'] ?? ''));

            if ($munCodigo === '00' && str_contains(mb_strtoupper($descripcion), 'EXTRANJER')) {
                $municipios[] = [
                    'codigo' => '0000',
                    'departamento_codigo' => '00',
                    'nombre' => $descripcion,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                continue;
            }

            $normalized = mb_strtoupper($this->stripAccents($descripcion));
            $depCode = null;

            foreach ($deptCodeByName as $deptName => $code) {
                if (str_starts_with($normalized, $deptName)) {
                    $depCode = $code;
                    break;
                }
            }

            if ($depCode === null) {
                continue;
            }

            $municipios[] = [
                'codigo' => $depCode . $munCodigo,
                'departamento_codigo' => $depCode,
                'nombre' => mb_convert_case(mb_strtolower($descripcion), MB_CASE_TITLE, 'UTF-8'),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($municipios)) {
            DB::table('dte_municipios')->upsert(
                $municipios,
                ['codigo'],
                ['departamento_codigo', 'nombre', 'activo', 'updated_at']
            );

            $officialMunCodes = collect($municipios)->pluck('codigo')->all();
            DB::table('dte_municipios')->whereNotIn('codigo', $officialMunCodes)->delete();
        }
    }
}
