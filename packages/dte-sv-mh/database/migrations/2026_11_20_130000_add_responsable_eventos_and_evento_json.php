<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El Evento de Invalidación exige identificar a quien realiza y a quien solicita el evento
 * (nombre, tipo y número de documento). Se configura por empresa. Además se conserva el JSON
 * del evento enviado (la Normativa exige conservar DTE y eventos tal como se transmitieron).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dte_settings', function (Blueprint $table) {
            $table->string('responsable_nombre', 100)->nullable();
            $table->string('responsable_tipo_doc', 2)->nullable();
            $table->string('responsable_num_doc', 20)->nullable();
        });

        Schema::table('dte_invalidaciones', function (Blueprint $table) {
            $table->json('json_evento')->nullable()->after('respuesta_hacienda');
        });
    }

    public function down(): void
    {
        Schema::table('dte_invalidaciones', function (Blueprint $table) {
            $table->dropColumn('json_evento');
        });
        Schema::table('dte_settings', function (Blueprint $table) {
            $table->dropColumn(['responsable_nombre', 'responsable_tipo_doc', 'responsable_num_doc']);
        });
    }
};
