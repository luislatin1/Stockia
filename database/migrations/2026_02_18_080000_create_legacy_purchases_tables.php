<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->unsignedInteger('legacy_id');
            $table->string('numero_doc', 30)->nullable();
            $table->date('fecha');
            $table->time('hora')->nullable();
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedInteger('dias_credito')->nullable();
            $table->boolean('anulada')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'legacy_id']);
            $table->index(['company_id', 'fecha']);
        });

        Schema::create('legacy_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_purchase_id')->constrained('legacy_purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('legacy_id');
            $table->decimal('cantidad', 11, 4)->default(0);
            $table->decimal('costo_unitario', 11, 4)->default(0);
            $table->decimal('subtotal', 11, 2)->default(0);
            $table->boolean('exento')->default(false);
            $table->timestamps();

            $table->unique(['legacy_purchase_id', 'legacy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_purchase_items');
        Schema::dropIfExists('legacy_purchases');
    }
};
