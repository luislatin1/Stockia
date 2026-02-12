<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_id')->nullable();

            $table->foreignId('currency_id')
                  ->constrained('currencies')
                  ->restrictOnDelete();

            $table->string('timezone')->default('America/El_Salvador');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};