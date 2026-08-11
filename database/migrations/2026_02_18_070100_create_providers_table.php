<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('legacy_id')->nullable();
            $table->string('nombre');
            $table->string('nit', 20)->nullable();
            $table->string('nrc', 20)->nullable();
            $table->string('dui', 15)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('direccion')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'legacy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
