<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dte_cat_014_unidades_medida', function (Blueprint $table) {
            $table->unsignedSmallInteger('codigo')->primary();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dte_cat_022_tipos_documento', function (Blueprint $table) {
            $table->string('codigo', 2)->primary();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dte_cat_024_tipos_invalidacion', function (Blueprint $table) {
            $table->string('codigo', 2)->primary();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dte_tributos', function (Blueprint $table) {
            $table->string('codigo', 20)->primary();
            $table->string('descripcion');
            $table->decimal('tasa', 8, 4)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dte_departamentos', function (Blueprint $table) {
            $table->string('codigo', 2)->primary();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dte_municipios', function (Blueprint $table) {
            $table->string('codigo', 4)->primary();
            $table->string('departamento_codigo', 2);
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('departamento_codigo')
                ->references('codigo')
                ->on('dte_departamentos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_municipios');
        Schema::dropIfExists('dte_departamentos');
        Schema::dropIfExists('dte_tributos');
        Schema::dropIfExists('dte_cat_024_tipos_invalidacion');
        Schema::dropIfExists('dte_cat_022_tipos_documento');
        Schema::dropIfExists('dte_cat_014_unidades_medida');
    }
};
