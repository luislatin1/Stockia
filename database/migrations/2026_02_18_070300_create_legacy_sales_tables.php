<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('legacy_id');
            $table->date('fecha');
            $table->time('hora')->nullable();
            $table->string('tipo', 30)->nullable();
            $table->string('tipo_documento', 20)->nullable();
            $table->string('numero_doc', 30)->nullable();
            $table->string('cliente_nombre')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->boolean('anulada')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'legacy_id']);
            $table->index(['company_id', 'fecha']);
        });

        Schema::create('legacy_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_sale_id')->constrained('legacy_sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('legacy_id');
            $table->string('descripcion')->nullable();
            $table->decimal('cantidad', 11, 4)->default(0);
            $table->decimal('precio_venta', 11, 4)->default(0);
            $table->decimal('subtotal', 11, 2)->default(0);
            $table->boolean('exento')->default(false);
            $table->timestamps();

            $table->unique(['legacy_sale_id', 'legacy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_sale_items');
        Schema::dropIfExists('legacy_sales');
    }
};
