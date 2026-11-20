<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Liga una Nota de Crédito al DTE que ajusta y al ajuste administrativo que la originó. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtes', function (Blueprint $table) {
            $table->unsignedBigInteger('dte_origen_id')->nullable()->after('sale_id')->index();
            $table->unsignedBigInteger('sale_adjustment_id')->nullable()->after('dte_origen_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('dtes', function (Blueprint $table) {
            $table->dropColumn(['dte_origen_id', 'sale_adjustment_id']);
        });
    }
};
