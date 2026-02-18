<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('nit', 20)->nullable()->after('tax_id');
            $table->string('nrc', 20)->nullable()->after('nit');
            $table->string('nombre_razon_social')->nullable()->after('nrc');
            $table->string('nombre_comercial')->nullable()->after('nombre_razon_social');
            $table->string('cod_actividad', 10)->nullable()->after('nombre_comercial');
            $table->string('desc_actividad')->nullable()->after('cod_actividad');
            $table->string('tipo_establecimiento', 2)->nullable()->after('desc_actividad');
            $table->string('telefono', 30)->nullable()->after('tipo_establecimiento');
            $table->string('correo', 120)->nullable()->after('telefono');
            $table->string('departamento', 2)->nullable()->after('correo');
            $table->string('municipio', 4)->nullable()->after('departamento');
            $table->string('direccion_complemento')->nullable()->after('municipio');
            $table->string('certificado_firma')->nullable()->after('direccion_complemento');
            $table->timestamp('certificado_expira_en')->nullable()->after('certificado_firma');
            $table->string('estado', 20)->default('ACTIVO')->after('certificado_expira_en');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('codigo', 50)->nullable()->after('name');
            $table->unsignedTinyInteger('tipo_item')->default(1)->after('codigo');
            $table->unsignedSmallInteger('uni_medida')->nullable()->after('tipo_item');
            $table->boolean('afecto_iva')->default(true)->after('uni_medida');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('company_id')->constrained('customers')->nullOnDelete();
            $table->string('tipo_dte', 2)->nullable()->after('document_type');
            $table->string('numero_interno', 30)->nullable()->after('tipo_dte');
            $table->decimal('gravadas', 12, 2)->default(0)->after('numero_interno');
            $table->decimal('exentas', 12, 2)->default(0)->after('gravadas');
            $table->decimal('no_sujetas', 12, 2)->default(0)->after('exentas');
            $table->decimal('iva', 12, 2)->default(0)->after('no_sujetas');
            $table->decimal('retencion_iva', 12, 2)->default(0)->after('iva');
            $table->decimal('retencion_renta', 12, 2)->default(0)->after('retencion_iva');
            $table->decimal('descuento_total', 12, 2)->default(0)->after('retencion_renta');
            $table->unique(['company_id', 'numero_interno']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('precio_unitario', 12, 2)->default(0)->after('price');
            $table->decimal('descuento', 12, 2)->default(0)->after('precio_unitario');
            $table->decimal('monto_gravado', 12, 2)->default(0)->after('descuento');
            $table->decimal('monto_exento', 12, 2)->default(0)->after('monto_gravado');
            $table->decimal('monto_no_sujeto', 12, 2)->default(0)->after('monto_exento');
            $table->decimal('iva_item', 12, 2)->default(0)->after('monto_no_sujeto');
            $table->decimal('total_item', 12, 2)->default(0)->after('iva_item');
            $table->unsignedTinyInteger('tipo_item')->default(1)->after('total_item');
            $table->unsignedSmallInteger('uni_medida')->nullable()->after('tipo_item');
        });

        DB::table('companies')
            ->whereNull('nit')
            ->update([
                'nit' => DB::raw('tax_id'),
                'nombre_razon_social' => DB::raw('COALESCE(legal_name, name)'),
                'nombre_comercial' => DB::raw('name'),
                'telefono' => DB::raw('fiscal_phone'),
                'correo' => DB::raw('fiscal_email'),
                'direccion_complemento' => DB::raw('fiscal_address'),
            ]);

        $codigoExpression = DB::getDriverName() === 'pgsql'
            ? "COALESCE(sku, barcode, 'PRD-' || id::text)"
            : "COALESCE(sku, barcode, CONCAT('PRD-', id))";

        DB::table('products')->update([
            'codigo' => DB::raw($codigoExpression),
        ]);

        DB::table('sales')->update([
            'tipo_dte' => DB::raw("CASE WHEN document_type = 'factura' THEN '01' ELSE NULL END"),
            'gravadas' => DB::raw('subtotal'),
            'iva' => DB::raw('tax_total'),
            'descuento_total' => 0,
            'retencion_iva' => 0,
            'retencion_renta' => 0,
        ]);

        $sales = DB::table('sales')->select('id', 'company_id')->orderBy('id')->get();
        foreach ($sales as $sale) {
            DB::table('sales')
                ->where('id', $sale->id)
                ->update(['numero_interno' => sprintf('V-%06d', $sale->id)]);
        }

        DB::table('sale_items')->update([
            'precio_unitario' => DB::raw('price'),
            'descuento' => 0,
            'monto_gravado' => DB::raw('subtotal'),
            'iva_item' => DB::raw('ROUND(subtotal * 0.13, 2)'),
            'total_item' => DB::raw('ROUND(subtotal * 1.13, 2)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'precio_unitario',
                'descuento',
                'monto_gravado',
                'monto_exento',
                'monto_no_sujeto',
                'iva_item',
                'total_item',
                'tipo_item',
                'uni_medida',
            ]);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'numero_interno']);
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn([
                'tipo_dte',
                'numero_interno',
                'gravadas',
                'exentas',
                'no_sujetas',
                'iva',
                'retencion_iva',
                'retencion_renta',
                'descuento_total',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'codigo',
                'tipo_item',
                'uni_medida',
                'afecto_iva',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'nit',
                'nrc',
                'nombre_razon_social',
                'nombre_comercial',
                'cod_actividad',
                'desc_actividad',
                'tipo_establecimiento',
                'telefono',
                'correo',
                'departamento',
                'municipio',
                'direccion_complemento',
                'certificado_firma',
                'certificado_expira_en',
                'estado',
            ]);
        });
    }
};
