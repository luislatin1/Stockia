<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lotes de documentos de una contingencia (Manual Técnico §3.4/§4.2.2): máximo 100 por lote y respuesta no
 * inmediata; se guarda el código de lote para consultar su resultado más tarde.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dte_lotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contingencia_id')->index();
            $table->string('id_envio', 36);
            $table->string('codigo_lote', 36)->nullable();
            $table->string('estado', 12)->default('ENVIADO'); // ENVIADO → PROCESADO
            $table->json('dtes');                             // ids de los dtes del lote
            $table->json('respuesta')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('consultado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_lotes');
    }
};
