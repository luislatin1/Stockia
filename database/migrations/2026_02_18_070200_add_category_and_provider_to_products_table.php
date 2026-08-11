<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('description')
                ->constrained('categories')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->after('category_id')
                ->constrained('providers')->nullOnDelete();
            $table->unsignedInteger('legacy_id')->nullable()->after('provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('provider_id');
            $table->dropColumn('legacy_id');
        });
    }
};
