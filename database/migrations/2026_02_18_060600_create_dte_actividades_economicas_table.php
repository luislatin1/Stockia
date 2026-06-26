<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dte_cat_actividades_economicas', function (Blueprint $table) {
            $table->string('codigo', 6)->primary();
            $table->string('descripcion', 300);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_cat_actividades_economicas');
    }
};
