<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('tipo_documento', 2);
            $table->string('numero_documento', 20);
            $table->string('nrc', 20)->nullable();
            $table->string('nombre');
            $table->string('departamento', 2)->nullable();
            $table->string('municipio', 4)->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 120)->nullable();
            $table->boolean('es_contribuyente')->default(false);
            $table->timestamps();

            $table->index(['company_id', 'nombre']);
            $table->unique(['company_id', 'tipo_documento', 'numero_documento'], 'customers_doc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
