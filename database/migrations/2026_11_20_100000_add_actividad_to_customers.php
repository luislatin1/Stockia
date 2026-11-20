<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DTE 2.0: el CCF v4 exige código y descripción de la actividad económica del receptor
 * (CAT-019). Quedan nulos hasta que se capturen; el CCF no se emite sin ellos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('cod_actividad', 6)->nullable()->after('distrito');
            $table->string('desc_actividad', 150)->nullable()->after('cod_actividad');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['cod_actividad', 'desc_actividad']);
        });
    }
};
