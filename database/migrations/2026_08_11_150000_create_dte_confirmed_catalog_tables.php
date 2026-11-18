<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'dte_cat_001_ambiente',
        'dte_cat_005_tipo_contingencia',
        'dte_cat_006_retencion_iva',
        'dte_cat_007_tipo_generacion',
        'dte_cat_010_tipo_servicio_medico',
        'dte_cat_017_formas_pago',
        'dte_cat_018_plazo',
        'dte_cat_021_otros_documentos',
        'dte_cat_023_tipo_doc_contingencia',
        'dte_cat_025_titulo_transporte',
        'dte_cat_026_tipo_donacion',
        'dte_cat_027_recinto_fiscal',
        'dte_cat_028_regimen',
        'dte_cat_029_tipo_persona',
        'dte_cat_030_transporte',
        'dte_cat_031_incoterms',
        'dte_cat_032_domicilio_fiscal',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::create($table, function (Blueprint $blueprint) {
                $blueprint->string('codigo', 20)->primary();
                $blueprint->string('descripcion');
                $blueprint->boolean('activo')->default(true);
                $blueprint->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            Schema::dropIfExists($table);
        }
    }
};
