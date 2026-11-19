<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tasas de impuestos con vigencia temporal. Una fila por tasa y período; el
 * cálculo toma la vigente en la fecha de la operación, de modo que un cambio de
 * tasa sea una fila nueva y no un cambio de código.
 *
 * Valores iniciales tomados de auditoria-modulos-sv.md §4 — VALIDAR CON EL
 * CONTADOR antes de producción. Las fechas "desde" son el inicio del registro
 * en Stockia, no la fecha de la ley.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30);                  // IVA, RET_IVA, PERC_IVA, PAGO_CUENTA
            $table->string('nombre');
            $table->string('tipo', 20);                    // impuesto | retencion | percepcion | pago_cuenta
            $table->decimal('tasa', 8, 4);                 // en porcentaje: 13.0000 = 13 %
            $table->string('tributo_cat015', 4)->nullable(); // código CAT-015 si aplica
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->unique(['codigo', 'vigente_desde']);
            $table->index(['codigo', 'vigente_desde', 'vigente_hasta']);
        });

        $now = now();
        $desde = '2000-01-01';

        $rates = [
            ['codigo' => 'IVA', 'nombre' => 'IVA', 'tipo' => 'impuesto', 'tasa' => 13, 'tributo_cat015' => '20',
                'nota' => 'Impuesto a la Transferencia de Bienes Muebles y a la Prestación de Servicios.'],
            ['codigo' => 'RET_IVA', 'nombre' => 'Retención de IVA', 'tipo' => 'retencion', 'tasa' => 1, 'tributo_cat015' => null,
                'nota' => 'Gran contribuyente, art. 162 Código Tributario, operaciones >= USD 100. Validar con contador.'],
            ['codigo' => 'PERC_IVA', 'nombre' => 'Percepción de IVA', 'tipo' => 'percepcion', 'tasa' => 1, 'tributo_cat015' => null,
                'nota' => 'Validar con contador.'],
            ['codigo' => 'PAGO_CUENTA', 'nombre' => 'Pago a cuenta', 'tipo' => 'pago_cuenta', 'tasa' => 1.75, 'tributo_cat015' => null,
                'nota' => 'Sobre ingresos brutos mensuales. Validar con contador.'],
        ];

        DB::table('tax_rates')->insert(array_map(fn (array $rate) => $rate + [
            'vigente_desde' => $desde,
            'vigente_hasta' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rates));
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
