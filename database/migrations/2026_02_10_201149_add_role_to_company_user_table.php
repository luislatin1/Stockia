<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('company_user', function (Blueprint $table) {
        $table->string('role')->nullable()->after('user_id');
    });

    // Valor por defecto para registros existentes
    DB::table('company_user')->update([
        'role' => 'owner',
    ]);

    Schema::table('company_user', function (Blueprint $table) {
        $table->string('role')->nullable(false)->change();
    });
}

public function down(): void
{
    Schema::table('company_user', function (Blueprint $table) {
        $table->dropColumn('role');
    });
}

};
