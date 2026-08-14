<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->string('tipo', 20);
            $table->decimal('monto', 12, 2);
            $table->string('numero_cheque', 30)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'bank_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_movements');
    }
};
