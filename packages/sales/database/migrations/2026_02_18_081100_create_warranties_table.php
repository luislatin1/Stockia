<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento');
            $table->string('estado', 20)->default('vigente');
            $table->string('descripcion_falla', 500)->nullable();
            $table->date('fecha_reclamo')->nullable();
            $table->string('resolucion', 500)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
