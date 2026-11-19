<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DTE 2.0: la dirección de emisor y receptor exige `distrito` (CAT-008) junto a
 * departamento y municipio. Los registros existentes quedan en NULL: no hay
 * forma de inferir el distrito, hay que capturarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('distrito', 2)->nullable()->after('municipio');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('distrito', 2)->nullable()->after('municipio');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('distrito');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('distrito');
        });
    }
};
