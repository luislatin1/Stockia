<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Establecimientos y puntos de venta fiscales (DTE 2.0).
 *
 * El número de control usa DTE-TT-<estab><pv>-<consecutivo>, donde estab es
 * [M|B|S|P]+3 dígitos (Casa Matriz, Bodega, Sucursal, Patio) y pv es P+3
 * dígitos. Los códigos los asigna Hacienda, por eso `codigo_confirmado`
 * arranca en false y el modo real no emite hasta que alguien lo confirme.
 */
return new class extends Migration
{
    /** CAT-009 → letra de la 3.ª sección del número de control. */
    private const LETRA_POR_TIPO = ['02' => 'M', '01' => 'S', '04' => 'B', '07' => 'P'];

    public function up(): void
    {
        Schema::create('dte_establecimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('codigo', 4);                    // M001, S002, B001, P001
            $table->string('tipo_establecimiento', 2);      // CAT-009
            $table->string('nombre');
            $table->string('departamento', 2)->nullable();  // CAT-012
            $table->string('municipio', 4)->nullable();     // dep+mun, igual que companies.municipio
            $table->string('distrito', 2)->nullable();      // CAT-008
            $table->string('direccion')->nullable();        // complemento (debe incluir el nombre del distrito)
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 120)->nullable();
            $table->boolean('codigo_confirmado')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'codigo']);
        });

        Schema::create('dte_puntos_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_id')->constrained('dte_establecimientos')->cascadeOnDelete();
            $table->string('codigo', 4);                    // P001
            $table->string('nombre');
            $table->boolean('codigo_confirmado')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['establecimiento_id', 'codigo']);
        });

        // Vínculos opcionales: una bodega o una caja del POS emite desde un
        // establecimiento / punto de venta concreto. Si no se asignan, se usa
        // el predeterminado de la empresa.
        if (Schema::hasTable('warehouses') && ! Schema::hasColumn('warehouses', 'dte_establecimiento_id')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->foreignId('dte_establecimiento_id')->nullable()
                    ->constrained('dte_establecimientos')->nullOnDelete();
            });
        }

        if (Schema::hasTable('pos_registers') && ! Schema::hasColumn('pos_registers', 'dte_punto_venta_id')) {
            Schema::table('pos_registers', function (Blueprint $table) {
                $table->foreignId('dte_punto_venta_id')->nullable()
                    ->constrained('dte_puntos_venta')->nullOnDelete();
            });
        }

        $this->backfill();
    }

    /** Una casa matriz + un punto de venta provisionales por empresa existente. */
    private function backfill(): void
    {
        $now = now();

        foreach (DB::table('companies')->get() as $company) {
            $tipo = in_array((string) $company->tipo_establecimiento, array_keys(self::LETRA_POR_TIPO), true)
                ? (string) $company->tipo_establecimiento
                : '02';

            $estabId = DB::table('dte_establecimientos')->insertGetId([
                'company_id' => $company->id,
                'codigo' => self::LETRA_POR_TIPO[$tipo].'001',
                'tipo_establecimiento' => $tipo,
                'nombre' => $company->nombre_comercial ?: $company->name,
                'departamento' => $company->departamento,
                'municipio' => $company->municipio,
                'distrito' => null,
                'direccion' => $company->direccion_complemento ?: $company->fiscal_address,
                'telefono' => $company->telefono ?: $company->fiscal_phone,
                'correo' => $company->correo ?: $company->fiscal_email,
                'codigo_confirmado' => false,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('dte_puntos_venta')->insert([
                'establecimiento_id' => $estabId,
                'codigo' => 'P001',
                'nombre' => 'Punto de venta principal',
                'codigo_confirmado' => false,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_registers') && Schema::hasColumn('pos_registers', 'dte_punto_venta_id')) {
            Schema::table('pos_registers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('dte_punto_venta_id');
            });
        }

        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'dte_establecimiento_id')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('dte_establecimiento_id');
            });
        }

        Schema::dropIfExists('dte_puntos_venta');
        Schema::dropIfExists('dte_establecimientos');
    }
};
