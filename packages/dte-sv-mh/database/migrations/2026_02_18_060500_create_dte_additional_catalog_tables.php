<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dte_cat_tipos_dte', function (Blueprint $table) {
            $table->string('codigo', 2)->primary();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dte_cat_condicion_operacion', function (Blueprint $table) {
            $table->string('codigo', 2)->primary();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dte_cat_tipo_modelo', function (Blueprint $table) {
            $table->string('codigo', 2)->primary();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dte_cat_tipo_operacion', function (Blueprint $table) {
            $table->string('codigo', 2)->primary();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dte_cat_tipo_item', function (Blueprint $table) {
            $table->string('codigo', 2)->primary();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('dte_cat_tipo_establecimiento', function (Blueprint $table) {
            $table->string('codigo', 2)->primary();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_cat_tipo_establecimiento');
        Schema::dropIfExists('dte_cat_tipo_item');
        Schema::dropIfExists('dte_cat_tipo_operacion');
        Schema::dropIfExists('dte_cat_tipo_modelo');
        Schema::dropIfExists('dte_cat_condicion_operacion');
        Schema::dropIfExists('dte_cat_tipos_dte');
    }
};
