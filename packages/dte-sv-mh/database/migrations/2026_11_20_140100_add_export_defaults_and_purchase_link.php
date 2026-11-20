<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Datos de exportación por empresa (iguales para todas sus facturas de exportación) y vínculo DTE ↔ compra. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dte_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('expo_tipo_item')->nullable();   // CAT-011: 1 bienes, 2 servicios
            $table->string('expo_recinto', 2)->nullable();               // CAT-027
            $table->string('expo_tipo_regimen', 4)->nullable();          // CAT-033
            $table->string('expo_regimen', 13)->nullable();              // CAT-028
            $table->string('expo_cod_incoterms', 2)->nullable();         // CAT-031
        });

        Schema::table('dtes', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_id')->nullable()->after('sale_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('dtes', function (Blueprint $table) {
            $table->dropColumn('purchase_id');
        });
        Schema::table('dte_settings', function (Blueprint $table) {
            $table->dropColumn(['expo_tipo_item', 'expo_recinto', 'expo_tipo_regimen', 'expo_regimen', 'expo_cod_incoterms']);
        });
    }
};
