<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('fiscal_address')->nullable()->after('tax_id');
            $table->string('fiscal_email')->nullable()->after('fiscal_address');
            $table->string('fiscal_phone')->nullable()->after('fiscal_email');
            $table->string('fiscal_regime')->nullable()->after('fiscal_phone');
            $table->string('invoice_prefix', 20)->nullable()->after('fiscal_regime');
            $table->text('ticket_footer')->nullable()->after('invoice_prefix');
            $table->string('logo_path')->nullable()->after('ticket_footer');
            $table->string('system_name')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'fiscal_address',
                'fiscal_email',
                'fiscal_phone',
                'fiscal_regime',
                'invoice_prefix',
                'ticket_footer',
                'logo_path',
                'system_name',
            ]);
        });
    }
};
