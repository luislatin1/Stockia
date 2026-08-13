<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('gravadas', 12, 2)->default(0)->after('subtotal');
            $table->decimal('exentas', 12, 2)->default(0)->after('gravadas');
            $table->decimal('no_sujetas', 12, 2)->default(0)->after('exentas');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['gravadas', 'exentas', 'no_sujetas']);
        });
    }
};
