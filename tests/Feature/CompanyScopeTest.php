<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CompanyScopeTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(string $name): Company
    {
        $currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'Dolar', 'symbol' => '$']
        );

        return Company::create([
            'name' => $name,
            'currency_id' => $currency->id,
        ]);
    }

    public function test_customer_queries_are_scoped_to_the_current_company(): void
    {
        $companyA = $this->makeCompany('Empresa A');
        $companyB = $this->makeCompany('Empresa B');

        $customerA = Customer::create([
            'company_id' => $companyA->id,
            'tipo_documento' => '13',
            'numero_documento' => '00000000-0',
            'nombre' => 'Cliente A',
        ]);

        $customerB = Customer::create([
            'company_id' => $companyB->id,
            'tipo_documento' => '13',
            'numero_documento' => '11111111-1',
            'nombre' => 'Cliente B',
        ]);

        Session::put('current_company_id', $companyA->id);

        $this->assertSame(1, Customer::count());
        $this->assertTrue(Customer::first()->is($customerA));
        $this->assertNull(Customer::find($customerB->id));

        Session::put('current_company_id', $companyB->id);

        $this->assertSame(1, Customer::count());
        $this->assertTrue(Customer::first()->is($customerB));
        $this->assertNull(Customer::find($customerA->id));
    }

    public function test_sale_queries_are_scoped_to_the_current_company(): void
    {
        $companyA = $this->makeCompany('Empresa A');
        $companyB = $this->makeCompany('Empresa B');

        $saleA = Sale::create(['company_id' => $companyA->id]);
        $saleB = Sale::create(['company_id' => $companyB->id]);

        Session::put('current_company_id', $companyA->id);

        $this->assertSame(1, Sale::count());
        $this->assertTrue(Sale::first()->is($saleA));
        $this->assertNull(Sale::find($saleB->id));
    }

    public function test_new_records_are_auto_assigned_to_the_current_company(): void
    {
        $company = $this->makeCompany('Empresa A');

        Session::put('current_company_id', $company->id);

        $customer = Customer::create([
            'tipo_documento' => '13',
            'numero_documento' => '00000000-0',
            'nombre' => 'Cliente sin company_id explicito',
        ]);

        $this->assertSame($company->id, $customer->company_id);
    }
}
