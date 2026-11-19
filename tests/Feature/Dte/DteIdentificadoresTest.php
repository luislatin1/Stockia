<?php

namespace Tests\Feature\Dte;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stockia\DteSvMh\Models\DteEstablecimiento;
use Stockia\DteSvMh\Services\DteEmissionService;
use Stockia\DteSvMh\Validators\ValidadorDocumentoSalvador;
use Tests\TestCase;

class DteIdentificadoresTest extends TestCase
{
    use RefreshDatabase;

    private function company(array $extra = []): Company
    {
        $currency = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dolar', 'symbol' => '$']);

        return Company::create($extra + [
            'name' => 'Empresa', 'currency_id' => $currency->id,
            'nit' => '0512-280887-101-3', 'nrc' => '23119-4',
        ]);
    }

    public function test_los_modelos_guardan_con_guiones_lo_valido_y_no_tocan_lo_dudoso(): void
    {
        $c = $this->company(['nit' => '05122808871013', 'nrc' => '231194']);
        $this->assertSame('0512-280887-101-3', $c->fresh()->nit);
        $this->assertSame('23119-4', $c->fresh()->nrc);

        $cli = Customer::create(['company_id' => $c->id, 'tipo_documento' => '13', 'numero_documento' => '012345678', 'nombre' => 'X']);
        $this->assertSame('01234567-8', $cli->fresh()->numero_documento);

        $p1 = Provider::create(['company_id' => $c->id, 'nombre' => 'A', 'nit' => '06140080110037', 'nrc' => '12345']);
        $p2 = Provider::create(['company_id' => $c->id, 'nombre' => 'B', 'nit' => '0---6', 'nrc' => '-1']);
        $this->assertSame('0614-008011-003-7', $p1->fresh()->nit);   // 14 dígitos: se formatea
        $this->assertSame('0---6', $p2->fresh()->nit);                // basura: intacta
        $this->assertSame('-1', $p2->fresh()->nrc);
    }

    public function test_un_dui_escrito_de_dos_formas_no_crea_clientes_duplicados(): void
    {
        $c = $this->company();
        Customer::create(['company_id' => $c->id, 'tipo_documento' => '13', 'numero_documento' => '012345678', 'nombre' => 'A']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Customer::create(['company_id' => $c->id, 'tipo_documento' => '13', 'numero_documento' => '01234567-8', 'nombre' => 'B']);
    }

    public function test_validador_acepta_dui_y_nit_con_o_sin_guion_y_nit_de_9_digitos(): void
    {
        $this->assertTrue(ValidadorDocumentoSalvador::validar('13', '01234567-8'));
        $this->assertTrue(ValidadorDocumentoSalvador::validar('13', '012345678'));
        $this->assertTrue(ValidadorDocumentoSalvador::validar('36', '0512-280887-101-3'));
        $this->assertTrue(ValidadorDocumentoSalvador::validar('36', '05122808871013'));
        $this->assertTrue(ValidadorDocumentoSalvador::validar('36', '012345678'));
        $this->assertTrue(ValidadorDocumentoSalvador::validarNrc('23119-4'));
    }

    public function test_validador_rechaza_valores_mal_formados(): void
    {
        foreach ([['13', '1234567-8'], ['13', '00000000-0'], ['36', '0614-008011-03-7'], ['36', '0000-000000-00-0']] as [$t, $n]) {
            try {
                ValidadorDocumentoSalvador::validar($t, $n);
                $this->fail("Debió rechazar tipo $t '$n'");
            } catch (\Exception $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }

        $this->expectException(\Exception::class);
        ValidadorDocumentoSalvador::validarNrc('-1');
    }

    /** Arma una venta completa y la emite en simulación; devuelve el DTE. */
    private function emitir(Company $c, ?Customer $cliente, string $tipoDte = '01')
    {
        $e = DteEstablecimiento::create(['company_id' => $c->id, 'codigo' => 'M001', 'tipo_establecimiento' => '02', 'nombre' => 'Matriz']);
        $e->puntosVenta()->create(['codigo' => 'P001', 'nombre' => 'Caja']);

        $w = Warehouse::withoutGlobalScopes()->create(['company_id' => $c->id, 'name' => 'B', 'code' => 'B-'.uniqid(), 'is_active' => true]);
        $prod = Product::withoutGlobalScopes()->create(['company_id' => $c->id, 'name' => 'Producto', 'price' => 100, 'stock' => 5]);

        $sale = new Sale();
        $sale->forceFill([
            'company_id' => $c->id, 'warehouse_id' => $w->id, 'customer_id' => $cliente?->id,
            'document_type' => 'factura', 'tipo_dte' => $tipoDte, 'status' => 'completed',
            'subtotal' => 100, 'gravadas' => 100, 'iva' => 13, 'tax_total' => 13, 'total' => 113, 'payment_method' => 'cash',
            'numero_interno' => 'V-T1',
        ])->save();
        $item = new SaleItem();
        $item->forceFill([
            'sale_id' => $sale->id, 'product_id' => $prod->id, 'quantity' => 1, 'price' => 100, 'precio_unitario' => 100,
            'monto_gravado' => 100, 'iva_item' => 13, 'total_item' => 113, 'subtotal' => 100, 'tipo_item' => 1, 'uni_medida' => 59,
        ])->save();

        return app(DteEmissionService::class)->emitForSale($sale->fresh());
    }

    public function test_el_dte_lleva_nit_y_nrc_del_emisor_sin_guiones(): void
    {
        $dte = $this->emitir($this->company(), null);

        $this->assertSame('05122808871013', $dte->json_original['emisor']['nit']);
        $this->assertSame('231194', $dte->json_original['emisor']['nrc']);
    }

    public function test_consumidor_final_sin_identificar_va_con_receptor_nulo_no_con_un_dui_falso(): void
    {
        $dte = $this->emitir($this->company(), null);

        $this->assertNull($dte->json_original['receptor']['numDocumento']);
        $this->assertNull($dte->json_original['receptor']['tipoDocumento']);
        $this->assertStringNotContainsString('00000000', json_encode($dte->json_original['receptor']));
    }

    public function test_el_receptor_con_dui_o_nit_va_sin_guiones(): void
    {
        $c = $this->company();
        $dui = Customer::create(['company_id' => $c->id, 'tipo_documento' => '13', 'numero_documento' => '01234567-8', 'nombre' => 'Persona', 'nrc' => '23119-4']);

        $dte = $this->emitir($c, $dui);

        $this->assertSame('012345678', $dte->json_original['receptor']['numDocumento']);
        $this->assertSame('231194', $dte->json_original['receptor']['nrc']);
    }

    public function test_un_nit_de_emisor_invalido_bloquea_la_emision_con_mensaje_claro(): void
    {
        $c = $this->company(['nit' => '0614-008011-03-7']);   // 13 dígitos, caso real de la base

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('NIT del emisor no es válido');
        $this->emitir($c, null);
    }
}
