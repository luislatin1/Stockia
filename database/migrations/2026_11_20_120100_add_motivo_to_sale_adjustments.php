<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_adjustments', function (Blueprint $table) {
            $table->string('motivo', 30)->nullable()->after('action_type');
            $table->string('dte_ruta', 20)->nullable()->after('motivo');
            $table->unsignedBigInteger('dte_id')->nullable()->after('dte_ruta');
            $table->unsignedBigInteger('replacement_sale_id')->nullable()->after('dte_id');
        });

        Schema::table('sale_adjustment_items', function (Blueprint $table) {
            // Ajuste de valor (precio/descuento): monto sin IVA acreditado por la línea; la cantidad va en 0.
            $table->decimal('credit_amount', 12, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('sale_adjustment_items', function (Blueprint $table) {
            $table->dropColumn('credit_amount');
        });
        Schema::table('sale_adjustments', function (Blueprint $table) {
            $table->dropColumn(['motivo', 'dte_ruta', 'dte_id', 'replacement_sale_id']);
        });
    }
};
