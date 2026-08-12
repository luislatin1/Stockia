<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedSmallInteger('dias_credito')->nullable()->after('document_type');
            $table->date('fecha_vencimiento')->nullable()->after('dias_credito');
            $table->decimal('saldo_pendiente', 12, 2)->default(0)->after('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['dias_credito', 'fecha_vencimiento', 'saldo_pendiente']);
        });
    }
};
