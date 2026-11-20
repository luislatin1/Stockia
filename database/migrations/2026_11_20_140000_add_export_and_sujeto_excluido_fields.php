<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Factura de Exportación: el receptor es del extranjero (país CAT-020, tipo de persona CAT-029).
 * Factura de Sujeto Excluido: el proveedor que vende sin ser contribuyente necesita dirección completa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('cod_pais', 2)->nullable();
            $table->string('nombre_pais', 50)->nullable();
            $table->unsignedTinyInteger('tipo_persona')->nullable();
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->string('departamento', 2)->nullable();
            $table->string('municipio', 4)->nullable();
            $table->string('distrito', 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['departamento', 'municipio', 'distrito']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['cod_pais', 'nombre_pais', 'tipo_persona']);
        });
    }
};
