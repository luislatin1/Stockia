<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dte_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete()->unique();
            $table->boolean('enabled')->default(true);
            $table->string('integration_mode', 20)->default('simulacion');
            $table->string('ambiente', 2)->default('00');
            $table->string('establecimiento', 4)->default('0001');
            $table->string('punto_venta', 4)->default('0001');
            $table->string('api_user')->nullable();
            $table->text('api_password')->nullable();
            $table->string('auth_url')->nullable();
            $table->string('send_url')->nullable();
            $table->string('signer_url')->nullable();
            $table->boolean('use_dummy_certificate')->default(true);
            $table->text('dummy_certificate_text')->nullable();
            $table->string('static_token')->nullable();
            $table->string('static_sello')->nullable();
            $table->string('static_estado', 20)->default('ACEPTADO');
            $table->json('static_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dte_settings');
    }
};
