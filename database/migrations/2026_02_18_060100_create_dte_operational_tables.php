<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dtes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('tipo_dte', 2);
            $table->string('codigo_generacion', 36)->unique();
            $table->string('numero_control', 40)->unique();
            $table->json('json_original');
            $table->json('json_firmado')->nullable();
            $table->string('sello_recepcion')->nullable();
            $table->json('respuesta_hacienda')->nullable();
            $table->string('estado', 20)->default('PENDIENTE');
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'estado']);
            $table->index(['company_id', 'tipo_dte']);
        });

        Schema::create('dte_correlativos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('tipo_dte', 2);
            $table->string('establecimiento', 4);
            $table->string('punto_venta', 4);
            $table->unsignedBigInteger('correlativo_actual')->default(0);
            $table->timestamps();

            $table->unique(
                ['company_id', 'tipo_dte', 'establecimiento', 'punto_venta'],
                'dte_correlativos_unique'
            );
        });

        Schema::create('dte_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('ambiente', 2)->default('00');
            $table->text('token');
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'ambiente']);
        });

        Schema::create('dte_send_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dte_id')->constrained('dtes')->cascadeOnDelete();
            $table->unsignedInteger('attempt_no')->default(1);
            $table->string('status', 20)->default('PENDING');
            $table->text('error_message')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->index(['dte_id', 'attempt_no']);
            $table->index(['status', 'attempted_at']);
        });

        Schema::create('dte_contingencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('codigo_evento', 30)->nullable();
            $table->unsignedSmallInteger('tipo_contingencia');
            $table->string('motivo', 500)->nullable();
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin')->nullable();
            $table->string('estado', 20)->default('ABIERTA');
            $table->timestamps();

            $table->index(['company_id', 'estado']);
        });

        Schema::create('dte_contingencia_dtes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contingencia_id')->constrained('dte_contingencias')->cascadeOnDelete();
            $table->foreignId('dte_id')->constrained('dtes')->cascadeOnDelete();
            $table->boolean('reenviado')->default(false);
            $table->timestamp('reenviado_at')->nullable();
            $table->timestamps();

            $table->unique(['contingencia_id', 'dte_id'], 'dte_contingencia_dte_unique');
        });

        Schema::create('dte_invalidaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dte_id')->constrained('dtes')->cascadeOnDelete();
            $table->string('tipo_invalidacion', 2);
            $table->string('motivo', 500);
            $table->timestamp('fecha_invalidacion');
            $table->string('estado_envio', 20)->default('PENDIENTE');
            $table->json('respuesta_hacienda')->nullable();
            $table->timestamps();

            $table->index(['dte_id', 'estado_envio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_invalidaciones');
        Schema::dropIfExists('dte_contingencia_dtes');
        Schema::dropIfExists('dte_contingencias');
        Schema::dropIfExists('dte_send_attempts');
        Schema::dropIfExists('dte_tokens');
        Schema::dropIfExists('dte_correlativos');
        Schema::dropIfExists('dtes');
    }
};
