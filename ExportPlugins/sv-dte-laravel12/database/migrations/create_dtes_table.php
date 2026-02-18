<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dtes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_generacion')->unique();
            $table->string('numero_control');
            $table->string('tipo_dte');
            $table->json('json_original');
            $table->json('json_firmado')->nullable();
            $table->json('respuesta_hacienda')->nullable();
            $table->string('estado')->default('PENDIENTE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dtes');
    }
};