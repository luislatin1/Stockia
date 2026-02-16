<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_user_warehouse', function (Blueprint $table) {
    $table->id();

    $table->foreignId('company_user_id')
        ->constrained('company_user')
        ->cascadeOnDelete();

    $table->foreignId('warehouse_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->timestamps();

    $table->unique(['company_user_id', 'warehouse_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_user_warehouse');
    }
};
