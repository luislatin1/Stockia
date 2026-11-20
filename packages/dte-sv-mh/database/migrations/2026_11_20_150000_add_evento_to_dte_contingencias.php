<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ciclo completo de contingencia (Manual Funcional v2.0 §VIII):
 *   ABIERTA → (evento transmitido y con sello) EVENTO_ENVIADO → (lote de documentos aceptado) CERRADA
 *   o EVENTO_RECHAZADO si Hacienda rechaza el evento (se corrige y se reintenta en 24 h).
 * Una contingencia pertenece a UN establecimiento (el evento informa un solo código de establecimiento).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dte_contingencias', function (Blueprint $table) {
            $table->string('establecimiento', 4)->nullable()->after('company_id');
            $table->string('codigo_generacion_evento', 36)->nullable();
            $table->json('json_evento')->nullable();
            $table->json('json_firmado_evento')->nullable();
            $table->string('sello_evento')->nullable();
            $table->json('respuesta_evento')->nullable();
            $table->timestamp('evento_enviado_at')->nullable();
            $table->timestamp('lote_limite_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dte_contingencias', function (Blueprint $table) {
            $table->dropColumn([
                'establecimiento', 'codigo_generacion_evento', 'json_evento', 'json_firmado_evento',
                'sello_evento', 'respuesta_evento', 'evento_enviado_at', 'lote_limite_at',
            ]);
        });
    }
};
