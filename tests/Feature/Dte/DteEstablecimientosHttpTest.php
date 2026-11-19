<?php

namespace Tests\Feature\Dte;

use App\Models\Company;
use App\Models\Currency;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Stockia\DteSvMh\Models\DteEstablecimiento;
use Stockia\DteSvMh\Models\DteSetting;
use Tests\TestCase;

class DteEstablecimientosHttpTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private function actAsAdmin(?Company $company = null): User
    {
        $currency = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dolar', 'symbol' => '$']);
        $this->company = $company ?? Company::create(['name' => 'Empresa A', 'currency_id' => $currency->id]);

        $user = User::factory()->create();
        $companyUserId = DB::table('company_user')->insertGetId([
            'company_id' => $this->company->id, 'user_id' => $user->id, 'role' => 'SuperAdmin',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $warehouse = Warehouse::withoutGlobalScopes()->create([
            'company_id' => $this->company->id, 'name' => 'Central', 'code' => 'B-'.uniqid(), 'is_active' => true,
        ]);

        DB::table('company_user_warehouse')->insert([
            'company_user_id' => $companyUserId, 'warehouse_id' => $warehouse->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)->withSession([
            'current_company_id' => $this->company->id,
            'current_warehouse_id' => $warehouse->id,
        ]);

        return $user;
    }

    private function estab(Company $c, string $codigo = 'M001', bool $confirmado = false): DteEstablecimiento
    {
        return DteEstablecimiento::withoutGlobalScopes()->create([
            'company_id' => $c->id, 'codigo' => $codigo, 'tipo_establecimiento' => '02',
            'nombre' => 'Matriz', 'codigo_confirmado' => $confirmado,
        ]);
    }

    public function test_la_pantalla_de_establecimientos_carga(): void
    {
        $this->actAsAdmin();
        $this->estab($this->company);

        $this->get(route('dte.establecimientos.index'))
            ->assertOk()
            ->assertSee('M001')
            ->assertSee('provisional', false);
    }

    public function test_crea_establecimiento_y_punto_de_venta_validos(): void
    {
        $this->actAsAdmin();

        $this->post(route('dte.establecimientos.store'), [
            'codigo' => 'S002', 'tipo_establecimiento' => '01', 'nombre' => 'Sucursal Centro',
        ])->assertSessionHasNoErrors();

        $e = DteEstablecimiento::where('codigo', 'S002')->firstOrFail();
        $this->assertSame($this->company->id, (int) $e->company_id);
        $this->assertFalse($e->codigo_confirmado);

        $this->post(route('dte.establecimientos.puntos-venta.store', $e), ['codigo' => 'P001', 'nombre' => 'Caja 1'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $e->puntosVenta()->count());
    }

    public function test_rechaza_codigos_con_formato_invalido_o_repetidos(): void
    {
        $this->actAsAdmin();
        $this->estab($this->company, 'M001');

        $this->post(route('dte.establecimientos.store'), ['codigo' => '0001', 'tipo_establecimiento' => '02', 'nombre' => 'X'])
            ->assertSessionHasErrors('codigo');
        $this->post(route('dte.establecimientos.store'), ['codigo' => 'X001', 'tipo_establecimiento' => '02', 'nombre' => 'X'])
            ->assertSessionHasErrors('codigo');
        $this->post(route('dte.establecimientos.store'), ['codigo' => 'M001', 'tipo_establecimiento' => '02', 'nombre' => 'X'])
            ->assertSessionHasErrors('codigo');

        $e = DteEstablecimiento::first();
        $this->post(route('dte.establecimientos.puntos-venta.store', $e), ['codigo' => 'P1', 'nombre' => 'X'])
            ->assertSessionHasErrors('codigo');
    }

    public function test_un_codigo_confirmado_no_se_puede_editar(): void
    {
        $this->actAsAdmin();
        $e = $this->estab($this->company, 'M001', true);

        $this->patch(route('dte.establecimientos.update', $e), [
            'codigo' => 'M999', 'tipo_establecimiento' => '02', 'nombre' => 'Renombrada', 'codigo_confirmado' => '1',
        ])->assertSessionHasNoErrors();

        $e->refresh();
        $this->assertSame('M001', $e->codigo);
        $this->assertSame('Renombrada', $e->nombre);
    }

    public function test_valida_que_el_distrito_pertenezca_al_departamento(): void
    {
        $this->actAsAdmin();
        DB::table('dte_departamentos')->insert([
            ['codigo' => '01', 'nombre' => 'Ahuachapán', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '02', 'nombre' => 'Santa Ana', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // El distrito 13 existe en Santa Ana (13 distritos) pero no en Ahuachapán (12).
        $this->post(route('dte.establecimientos.store'), [
            'codigo' => 'S003', 'tipo_establecimiento' => '01', 'nombre' => 'X', 'departamento' => '01', 'distrito' => '13',
        ])->assertSessionHasErrors('distrito');

        $this->post(route('dte.establecimientos.store'), [
            'codigo' => 'S004', 'tipo_establecimiento' => '01', 'nombre' => 'X', 'departamento' => '02', 'distrito' => '13',
        ])->assertSessionHasNoErrors();
    }

    public function test_no_se_puede_tocar_el_punto_de_venta_de_otra_empresa(): void
    {
        $this->actAsAdmin();

        $otra = Company::create(['name' => 'Empresa B', 'currency_id' => $this->company->currency_id]);
        $eAjeno = $this->estab($otra, 'M001');
        $pvAjeno = $eAjeno->puntosVenta()->create(['codigo' => 'P001', 'nombre' => 'Ajena']);

        $this->patch(route('dte.puntos-venta.update', $pvAjeno), ['codigo' => 'P001', 'nombre' => 'Hackeada'])
            ->assertNotFound();

        $this->assertSame('Ajena', $pvAjeno->fresh()->nombre);
    }

    public function test_la_pantalla_de_administracion_no_muestra_las_contrasenas(): void
    {
        $this->actAsAdmin();
        DteSetting::create([
            'company_id' => $this->company->id, 'api_password' => 'Secreta#123456', 'signer_password' => 'Llave#987654',
        ]);

        $html = $this->get(route('dte.admin.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Secreta#123456', $html);
        $this->assertStringNotContainsString('Llave#987654', $html);
        $this->assertStringContainsString('Guardada', $html);
    }

    public function test_dejar_la_contrasena_en_blanco_conserva_la_guardada(): void
    {
        $this->actAsAdmin();
        DteSetting::create([
            'company_id' => $this->company->id, 'api_password' => 'Secreta#123456', 'signer_password' => 'Llave#987654',
        ]);

        $this->patch(route('dte.admin.settings.update'), [
            'integration_mode' => 'simulacion', 'ambiente' => '00', 'numeracion_modo' => 'por_establecimiento',
            'api_password' => '', 'signer_password' => '',
        ])->assertSessionHasNoErrors();

        $s = DteSetting::where('company_id', $this->company->id)->first();
        $this->assertSame('Secreta#123456', $s->api_password);
        $this->assertSame('Llave#987654', $s->signer_password);
        $this->assertSame('por_establecimiento', $s->numeracion_modo);

        $this->patch(route('dte.admin.settings.update'), [
            'integration_mode' => 'simulacion', 'ambiente' => '00', 'numeracion_modo' => 'centralizada',
            'api_password' => 'Nueva#1234567',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Nueva#1234567', DteSetting::where('company_id', $this->company->id)->first()->api_password);
    }

    public function test_empresa_y_cliente_muestran_el_campo_distrito_y_lo_guardan(): void
    {
        $this->actAsAdmin();

        $this->get(route('core.admin.index'))->assertOk()->assertSee('name="distrito"', false);
        $this->get(route('dte.customers.create'))->assertOk()->assertSee('name="distrito"', false);

        $this->post(route('dte.customers.store'), [
            'tipo_documento' => '13', 'numero_documento' => '01234567-8', 'nombre' => 'Cliente con distrito',
            'departamento' => '02', 'distrito' => '03',
        ])->assertSessionHasNoErrors();

        $this->assertSame('03', \App\Models\Customer::where('numero_documento', '01234567-8')->value('distrito'));
    }
}
