<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0)->after('status');
            $table->decimal('tax_total', 10, 2)->default(0)->after('subtotal');
            $table->string('payment_method')->default('cash')->after('tax_total');
            $table->decimal('cash_received', 10, 2)->nullable()->after('payment_method');
            $table->decimal('change_amount', 10, 2)->default(0)->after('cash_received');
            $table->string('document_type')->default('ticket')->after('change_amount');
        });

        DB::table('sales')
            ->whereNull('cash_received')
            ->update([
                'subtotal' => DB::raw('total'),
                'tax_total' => 0,
                'payment_method' => 'cash',
                'cash_received' => DB::raw('total'),
                'change_amount' => 0,
                'document_type' => 'ticket',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'tax_total',
                'payment_method',
                'cash_received',
                'change_amount',
                'document_type',
            ]);
        });
    }
};

