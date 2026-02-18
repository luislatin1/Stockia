<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dte_settings')) {
            DB::table('dte_settings')
                ->where('integration_mode', 'static')
                ->update(['integration_mode' => 'simulacion']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dte_settings')) {
            DB::table('dte_settings')
                ->where('integration_mode', 'simulacion')
                ->update(['integration_mode' => 'static']);
        }
    }
};
