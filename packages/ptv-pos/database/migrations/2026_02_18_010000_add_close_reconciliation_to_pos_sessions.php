<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->decimal('expected_cash', 10, 2)->nullable()->after('closing_cash');
            $table->decimal('cash_difference', 10, 2)->nullable()->after('expected_cash');
            $table->string('closing_note', 255)->nullable()->after('cash_difference');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropColumn(['expected_cash', 'cash_difference', 'closing_note']);
        });
    }
};
