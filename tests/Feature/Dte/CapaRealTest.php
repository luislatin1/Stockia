<?php

namespace Tests\Feature\Dte;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Stockia\DteSvMh\Models\Dte;
use Stockia\DteSvMh\Models\DteEstablecimiento;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\DteEmissionService;
use Stockia\DteSvMh\Services\Real\MhEndpoints;
use Stockia\DteSvMh\Services\Real\RealAuthService;
use Stockia\DteSvMh\Services\Real\RealSendService;
use Stockia\DteSvMh\Services\Real\RealSigner;
use Stockia\DteSvMh\Services\Real\RealTransmisionService;
use Tests\TestCase;

/**
 * Capa real de transmisión contra el contrato del Manual Técnico de Integración v2.0, con respuestas SIMULADAS
 * (Http::fake) calcadas de los ejemplos del manual. No se necesitan credenciales ni red.
 */
class CapaRealTest extends TestCase
{
    use RefreshDatabase;

    private const SELLO = '20269E9D4DC0292F4681AD759B0B0F5CA99DC23C';

    private const URL_AUTH = 'https://apitest.dtes.mh.gob.sv/seguridad/auth';

    private const URL_RECEPCION = 'https://apitest.dtes.mh.gob.sv/fesv/recepciondte';

    private const URL_CONSULTA = 'https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadte';

    private const URL_FIRMADOR = 'http://127.0.0.1:8113/firmardocumento/';

    private Company $company;

    private Warehouse $warehouse;

    private DteSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $currency = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'Dolar', 'symbol' => '$']);
        $this->company = Company::create([
            'name' => 'Empresa', 'currency_id' => $currency->id, 'nit' => '0512-280887-101-3', 'nrc' => '23119-4',
            'cod_actividad' => '47112', 'desc_actividad' => 'Venta al por menor', 'departamento' => '06', 'municipio' => '21',
            'distrito' => '01', 'direccion_complemento' => 'Calle Principal 1', 'telefono' => '22223333', 'correo' => 'v@ejemplo.com.sv',
        ]);
        $e = DteEstablecimiento::create(['company_id' => $this->company->id, 'codigo' => 'M001', 'tipo_establecimiento' => '02', 'nombre' => 'Matriz', 'codigo_confirmado' => true]);
        $e->puntosVenta()->create(['codigo' => 'P001', 'nombre' => 'Caja', 'codigo_confirmado' => true]);
        $this->warehouse = Warehouse::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'name' => 'B', 'code' => 'B-1', 'is_active' => true]);

        // Datos DUMMY: credenciales y llave inventadas; nada sale a la red porque Http::fake lo intercepta.
        $this->settings = DteSetting::create([
            'company_id' => $this->company->id, 'enabled' => true, 'integration_mode' => 'real', 'ambiente' => '00',
            'api_user' => '05122808871013', 'api_password' => 'Dummy-Password#2026x', 'signer_password' => 'llave-privada-dummy',
            'responsable_nombre' => 'ANA LOPEZ', 'responsable_tipo_doc' => '13', 'responsable_num_doc' => '012345678',
        ]);
    }

    /** Respuestas por defecto del manual; cada prueba puede sobreescribir las que le interesan. */
    private function fakes(array $extra = []): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake($extra + [
            self::URL_AUTH => Http::response(['status' => 'OK', 'body' => ['user' => '05122808871013', 'token' => 'Bearer eyJ.dummy.token', 'tokenType' => 'Bearer']]),
            self::URL_FIRMADOR => Http::response(['status' => 'OK', 'body' => 'eyJhbGciOiJSUzUxMiJ9.dummy.firma']),
            self::URL_RECEPCION => Http::response($this->procesado()),
            self::URL_CONSULTA => Http::response(['estado' => 'RECHAZADO', 'codigoMsg' => '006', 'descripcionMsg' => 'NO EXISTE'], 400),
            'https://apitest.dtes.mh.gob.sv/fesv/contingencia' => Http::response(['estado' => 'RECIBIDO', 'fechaHora' => '20/11/2026 10:09:18', 'mensaje' => 'Documento recibido', 'selloRecibido' => self::SELLO, 'observaciones' => []]),
            'https://apitest.dtes.mh.gob.sv/fesv/anulardte' => Http::response(['version' => 2, 'ambiente' => '00', 'versionApp' => 1, 'estado' => 'PROCESADO', 'selloRecibido' => self::SELLO, 'fhProcesamiento' => '20/11/2026 09:50:52', 'codigoMsg' => '001', 'descripcionMsg' => 'Invalidación Recibida y Procesada', 'observaciones' => null]),
        ]);
    }

    private function procesado(array $extra = []): array
    {
        return $extra + ['version' => 2, 'ambiente' => '00', 'versionApp' => 2, 'estado' => 'PROCESADO', 'codigoGeneracion' => 'FF84E5DB-79C5-42CE-B415-EC510C53EFB5',
            'selloRecibido' => self::SELLO, 'fhProcesamiento' => '20/11/2026 13:29:04', 'clasificaMsg' => '10', 'codigoMsg' => '001', 'descripcionMsg' => 'RECIBIDO', 'observaciones' => []];
    }

    private function firmado(): array
    {
        return ['jsonFirmado' => 'eyJ.documento.firmado', 'id_envio' => 77, 'payload' => [
            'identificacion' => ['version' => 2, 'tipoDte' => '01', 'codigoGeneracion' => 'FF84E5DB-79C5-42CE-B415-EC510C53EFB5'],
            'emisor' => ['nit' => '05122808871013'],
        ]];
    }

    private function auth(): RealAuthService
    {
        return new RealAuthService();
    }

    // ------------------------------------------------------------- URLs y ambientes

    public function test_las_urls_salen_del_ambiente_sin_barra_final(): void
    {
        $s = $this->settings;
        $this->assertSame('https://apitest.dtes.mh.gob.sv/seguridad/auth', MhEndpoints::auth($s));
        $this->assertSame('https://apitest.dtes.mh.gob.sv/fesv/recepciondte', MhEndpoints::recepcion($s));
        $this->assertSame('https://apitest.dtes.mh.gob.sv/fesv/recepcionlote', MhEndpoints::lote($s));
        $this->assertSame('https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadte', MhEndpoints::consulta($s));
        $this->assertSame('https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadtelote/ABC', MhEndpoints::consultaLote($s, 'ABC'));
        $this->assertSame('https://apitest.dtes.mh.gob.sv/fesv/contingencia', MhEndpoints::contingencia($s));
        $this->assertSame('https://apitest.dtes.mh.gob.sv/fesv/anulardte', MhEndpoints::anular($s));

        $s->ambiente = '01';
        $this->assertSame('https://api.dtes.mh.gob.sv/fesv/recepciondte', MhEndpoints::recepcion($s));

        $s->ambiente = '00';
        $s->send_url = 'https://apitest.dtes.mh.gob.sv/fesv/recepciondte/'; // con barra: se corrige
        $this->assertSame('https://apitest.dtes.mh.gob.sv/fesv/recepciondte', MhEndpoints::recepcion($s));
    }

    public function test_el_firmador_lleva_barra_final_y_su_url_se_puede_cambiar(): void
    {
        $this->assertSame('http://127.0.0.1:8113/firmardocumento/', MhEndpoints::firmador($this->settings));

        $this->settings->signer_url = 'https://firmador.cliente.local:8113/firmardocumento';
        $this->assertSame('https://firmador.cliente.local:8113/firmardocumento/', MhEndpoints::firmador($this->settings));
    }

    public function test_no_se_mezclan_ambientes_de_pruebas_y_produccion(): void
    {
        $this->settings->ambiente = '00';
        $this->settings->send_url = 'https://api.dtes.mh.gob.sv/fesv/recepciondte';
        try {
            MhEndpoints::recepcion($this->settings);
            $this->fail('Pruebas contra producción debe bloquearse');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('PRODUCCIÓN', $e->getMessage());
        }

        $this->settings->ambiente = '01';
        $this->settings->send_url = 'https://apitest.dtes.mh.gob.sv/fesv/recepciondte';
        $this->expectExceptionMessage('PRUEBAS');
        MhEndpoints::recepcion($this->settings);
    }

    // ------------------------------------------------------------- autenticación

    public function test_autentica_con_formulario_user_pwd_y_guarda_el_token_bearer(): void
    {
        $this->fakes();
        $r = $this->auth()->authenticate($this->settings);

        $this->assertSame('Bearer eyJ.dummy.token', $r['body']['token']);
        Http::assertSent(function (Request $req) {
            return $req->url() === self::URL_AUTH
                && str_contains($req->header('Content-Type')[0], 'application/x-www-form-urlencoded')
                && $req['user'] === '05122808871013' && $req['pwd'] === 'Dummy-Password#2026x'
                && $req->header('User-Agent')[0] === MhEndpoints::USER_AGENT;
        });

        // El token se reutiliza: no se autentica en cada envío (Manual §1.1).
        $this->auth()->authenticate($this->settings);
        Http::assertSentCount(1);

        $vence = DB::table('dte_tokens')->value('expires_at');
        $this->assertEqualsWithDelta(48 * 3600, \Carbon\Carbon::parse($vence)->diffInSeconds(now(), true), 120, 'pruebas: 48 h');
    }

    public function test_el_token_de_produccion_dura_24_horas(): void
    {
        $this->settings->update(['ambiente' => '01']);
        Http::fake(['https://api.dtes.mh.gob.sv/seguridad/auth' => Http::response(['status' => 'OK', 'body' => ['token' => 'sin-prefijo']])]);

        $r = $this->auth()->authenticate($this->settings->fresh());

        $this->assertSame('Bearer sin-prefijo', $r['body']['token'], 'se normaliza el prefijo');
        $this->assertEqualsWithDelta(24 * 3600, \Carbon\Carbon::parse(DB::table('dte_tokens')->value('expires_at'))->diffInSeconds(now(), true), 120);
    }

    public function test_credenciales_invalidas_dan_un_mensaje_claro_sin_exponer_la_contrasena(): void
    {
        $this->fakes([self::URL_AUTH => Http::response(['status' => 'ERROR', 'error' => 'Unauthorized', 'message' => 'Usuario no valido'], 401)]);

        try {
            $this->auth()->authenticate($this->settings);
            $this->fail('Debió rechazar');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Usuario no valido', $e->getMessage());
            $this->assertStringNotContainsString('Dummy-Password', $e->getMessage());
        }
        $this->assertSame(0, DB::table('dte_tokens')->count());
    }

    public function test_sin_credenciales_no_llama_a_hacienda(): void
    {
        Http::fake();
        $this->settings->update(['api_user' => null, 'api_password' => null]);

        try {
            $this->auth()->authenticate($this->settings->fresh());
            $this->fail('Sin credenciales debe fallar');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('usuario y la contraseña', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    // ------------------------------------------------------------- firma

    public function test_firma_con_el_contrato_del_firmador_oficial(): void
    {
        $this->fakes();
        $dte = ['identificacion' => ['codigoGeneracion' => 'ABC'], 'emisor' => ['nit' => '05122808871013']];

        $f = (new RealSigner())->sign($dte, new Sale(), $this->settings);

        $this->assertSame('eyJhbGciOiJSUzUxMiJ9.dummy.firma', $f['jsonFirmado']);
        $this->assertSame($dte, $f['payload']);
        Http::assertSent(fn (Request $req) => $req->url() === self::URL_FIRMADOR
            && $req['nit'] === '05122808871013' && $req['activo'] === true
            && $req['passwordPri'] === 'llave-privada-dummy' && $req['dteJson'] === $dte);
    }

    public function test_el_firmador_responde_200_con_status_error_y_se_traduce(): void
    {
        $this->fakes([self::URL_FIRMADOR => Http::response(['status' => 'ERROR', 'body' => ['codigo' => '809', 'mensaje' => ['Formato de NIT no valido - (00000000000000)']]])]);

        $this->expectExceptionMessage('[809]');
        (new RealSigner())->sign(['emisor' => ['nit' => '00000000000000']], new Sale(), $this->settings);
    }

    public function test_si_el_firmador_no_esta_levantado_se_explica(): void
    {
        $this->fakes([self::URL_FIRMADOR => fn () => throw new ConnectionException('refused')]);

        $this->expectExceptionMessage('El firmador no responde');
        (new RealSigner())->sign(['emisor' => ['nit' => '05122808871013']], new Sale(), $this->settings);
    }

    public function test_sin_contrasena_de_llave_no_se_intenta_firmar(): void
    {
        Http::fake();
        $this->settings->update(['signer_password' => null]);

        $this->expectExceptionMessage('contraseña de la llave privada');
        (new RealSigner())->sign(['emisor' => ['nit' => '05122808871013']], new Sale(), $this->settings->fresh());
    }

    // ------------------------------------------------------------- recepción y política de reintentos

    private function enviador(): RealSendService
    {
        return new RealSendService($this->auth(), new RealTransmisionService($this->auth()));
    }

    public function test_recepcion_aceptada_manda_el_cuerpo_del_manual_y_devuelve_el_sello(): void
    {
        $this->fakes();
        $r = $this->enviador()->send($this->firmado(), $this->settings);

        $this->assertSame(['ACEPTADO', self::SELLO], [$r['status'], $r['sello']]);
        Http::assertSent(fn (Request $req) => $req->url() === self::URL_RECEPCION
            && $req->header('Authorization')[0] === 'Bearer eyJ.dummy.token'
            && $req['ambiente'] === '00' && $req['idEnvio'] === 77 && $req['version'] === 2
            && $req['tipoDte'] === '01' && $req['documento'] === 'eyJ.documento.firmado'
            && $req['codigoGeneracion'] === 'FF84E5DB-79C5-42CE-B415-EC510C53EFB5');
    }

    public function test_recibido_con_observaciones_sigue_siendo_aceptado(): void
    {
        $this->fakes([self::URL_RECEPCION => Http::response($this->procesado(['codigoMsg' => '002', 'descripcionMsg' => 'RECIBIDO CON OBSERVACIONES', 'observaciones' => ['identificacion.fecEmi Difiere de la fecha de envío.']]))]);

        $r = $this->enviador()->send($this->firmado(), $this->settings);

        $this->assertSame('ACEPTADO', $r['status']);
        $this->assertSame(['identificacion.fecEmi Difiere de la fecha de envío.'], $r['observaciones']);
    }

    public function test_el_rechazo_trae_codigo_descripcion_y_observaciones(): void
    {
        $this->fakes([self::URL_RECEPCION => Http::response(['version' => 2, 'ambiente' => '00', 'estado' => 'RECHAZADO', 'selloRecibido' => null,
            'codigoMsg' => '018', 'descripcionMsg' => 'CALCULO INCORRECTO', 'observaciones' => ['resumen.totalPagar']], 400)]);

        $r = $this->enviador()->send($this->firmado(), $this->settings);

        $this->assertSame(['RECHAZADO', null], [$r['status'], $r['sello']]);
        $this->assertStringContainsString('[018] CALCULO INCORRECTO', $r['error']);
        $this->assertStringContainsString('resumen.totalPagar', $r['error']);
    }

    public function test_sin_respuesta_consulta_primero_y_no_reenvia_si_ya_llego(): void
    {
        $recepciones = 0;
        $this->fakes([
            self::URL_RECEPCION => function () use (&$recepciones) {
                $recepciones++;
                throw new ConnectionException('timeout 8s');
            },
            self::URL_CONSULTA => Http::response($this->procesado()),
        ]);

        $r = $this->enviador()->send($this->firmado(), $this->settings);

        $this->assertSame('ACEPTADO', $r['status']);
        $this->assertSame(1, $recepciones, 'si la consulta confirma que llegó, no se reenvía');
        Http::assertSent(fn (Request $req) => $req->url() === self::URL_CONSULTA && $req['nitEmisor'] === '05122808871013' && $req['tdte'] === '01');
    }

    public function test_si_no_llego_se_reenvia_y_sale_a_la_segunda(): void
    {
        $n = 0;
        $this->fakes([self::URL_RECEPCION => function () use (&$n) {
            return ++$n === 1 ? throw new ConnectionException('timeout') : Http::response($this->procesado());
        }]);

        $r = $this->enviador()->send($this->firmado(), $this->settings);

        $this->assertSame('ACEPTADO', $r['status']);
        $this->assertSame(2, $n);
    }

    public function test_tras_un_envio_y_dos_reenvios_queda_sin_respuesta(): void
    {
        $n = 0;
        $this->fakes([self::URL_RECEPCION => function () use (&$n) {
            $n++;
            throw new ConnectionException('timeout');
        }]);

        $r = $this->enviador()->send($this->firmado(), $this->settings);

        $this->assertSame('SIN_RESPUESTA', $r['status']);
        $this->assertSame(3, $n, '1 envío + máximo 2 reenvíos (Manual §3.3)');
        $this->assertStringContainsString('pendiente', $r['error']);
    }

    public function test_un_error_de_servidor_5xx_activa_la_politica_de_reintentos(): void
    {
        $n = 0;
        $this->fakes([self::URL_RECEPCION => function () use (&$n) {
            return ++$n < 3 ? Http::response('Bad gateway', 502) : Http::response($this->procesado());
        }]);

        $this->assertSame('ACEPTADO', $this->enviador()->send($this->firmado(), $this->settings)['status']);
        $this->assertSame(3, $n);
    }

    public function test_un_401_renueva_el_token_una_vez_y_reintenta(): void
    {
        $tokens = 0;
        $n = 0;
        $this->fakes([
            self::URL_AUTH => function () use (&$tokens) {
                $tokens++;

                return Http::response(['status' => 'OK', 'body' => ['token' => "Bearer token-{$tokens}"]]);
            },
            self::URL_RECEPCION => function () use (&$n) {
                return ++$n === 1 ? Http::response(['status' => 'ERROR', 'message' => 'Token invalido'], 401) : Http::response($this->procesado());
            },
        ]);

        $this->assertSame('ACEPTADO', $this->enviador()->send($this->firmado(), $this->settings)['status']);
        $this->assertSame(2, $tokens, 'segundo token tras el 401');
        Http::assertSent(fn (Request $req) => $req->url() === self::URL_RECEPCION && ($req->header('Authorization')[0] ?? '') === 'Bearer token-2');
    }

    public function test_un_documento_sin_firmar_no_se_envia(): void
    {
        Http::fake();
        $this->expectExceptionMessage('no está firmado');
        $this->enviador()->send(['payload' => $this->firmado()['payload']], $this->settings);
        Http::assertNothingSent();
    }

    // ------------------------------------------------------------- emisión de punta a punta en modo real

    private function venta(): Sale
    {
        $p = Product::withoutGlobalScopes()->create(['company_id' => $this->company->id, 'name' => 'Prod '.uniqid(), 'price' => 100, 'stock' => 5]);
        $sale = new Sale();
        $sale->forceFill([
            'company_id' => $this->company->id, 'warehouse_id' => $this->warehouse->id, 'document_type' => 'factura', 'tipo_dte' => '01', 'status' => 'completed',
            'subtotal' => 100, 'gravadas' => 100, 'iva' => 13, 'tax_total' => 13, 'total' => 113, 'payment_method' => 'cash', 'numero_interno' => 'V-'.uniqid(),
        ])->save();
        $i = new SaleItem();
        $i->forceFill(['sale_id' => $sale->id, 'product_id' => $p->id, 'quantity' => 1, 'price' => 100, 'precio_unitario' => 100, 'monto_gravado' => 100, 'iva_item' => 13, 'total_item' => 113, 'subtotal' => 100, 'tipo_item' => 1, 'uni_medida' => 59])->save();

        return $sale->fresh();
    }

    public function test_emision_real_firma_transmite_y_guarda_el_sello(): void
    {
        $this->fakes();
        $dte = app(DteEmissionService::class)->emitForSale($this->venta());

        $this->assertSame(['ACEPTADO', self::SELLO], [$dte->estado, $dte->sello_recepcion]);
        $this->assertSame('eyJhbGciOiJSUzUxMiJ9.dummy.firma', $dte->json_firmado['jsonFirmado']);
        Http::assertSent(fn (Request $req) => $req->url() === self::URL_FIRMADOR && $req['dteJson']['identificacion']['tipoDte'] === '01');
        Http::assertSent(fn (Request $req) => $req->url() === self::URL_RECEPCION && $req['idEnvio'] === $dte->id);

        // Archivo DTE entregable al cliente: estructura + firma + sello (Normativa Anexo I 4.1).
        $archivo = $dte->archivoDte();
        $this->assertSame('eyJhbGciOiJSUzUxMiJ9.dummy.firma', $archivo['firmaElectronica']);
        $this->assertSame(self::SELLO, $archivo['selloRecibido']);
        $this->assertSame($dte->codigo_generacion.'.json', $dte->nombreArchivo());
    }

    public function test_emision_real_rechazada_queda_rechazada_con_el_motivo(): void
    {
        $this->fakes([self::URL_RECEPCION => Http::response(['estado' => 'RECHAZADO', 'codigoMsg' => '003', 'descripcionMsg' => 'VALOR NO VALIDO', 'observaciones' => ['emisor.nrc']], 400)]);

        $dte = app(DteEmissionService::class)->emitForSale($this->venta());

        $this->assertSame('RECHAZADO', $dte->estado);
        $this->assertSame('003', $dte->respuesta_hacienda['codigoMsg']);
        $this->assertNull($dte->sello_recepcion);
    }

    public function test_sin_respuesta_el_documento_queda_pendiente_y_se_retransmite_despues(): void
    {
        $this->fakes([self::URL_RECEPCION => fn () => throw new ConnectionException('timeout')]);
        $svc = app(DteEmissionService::class);

        $dte = $svc->emitForSale($this->venta());
        $this->assertSame('PENDIENTE', $dte->estado);
        $this->assertNotEmpty($dte->json_firmado['jsonFirmado'], 'queda firmado para retransmitirse');
        $this->assertSame(1, DB::table('dte_send_attempts')->where('dte_id', $dte->id)->where('status', 'SIN_RESPUESTA')->count(), 'registra el intento');

        // Vuelve la conexión.
        $this->fakes();
        $this->assertSame(1, $svc->reintentarPendientes($this->company->id));
        $this->assertSame(['ACEPTADO', self::SELLO], [$dte->fresh()->estado, $dte->fresh()->sello_recepcion]);
        $this->assertSame(0, $svc->reintentarPendientes($this->company->id), 'nada más que reintentar');
    }

    public function test_el_modo_real_no_emite_con_codigos_de_establecimiento_sin_confirmar(): void
    {
        $this->fakes();
        DteEstablecimiento::withoutGlobalScopes()->where('company_id', $this->company->id)->update(['codigo_confirmado' => false]);

        $this->expectExceptionMessage('confirme con Hacienda');
        app(DteEmissionService::class)->emitForSale($this->venta());
    }

    // ------------------------------------------------------------- invalidación

    public function test_invalidacion_real_firma_el_evento_y_lo_transmite_a_anulardte(): void
    {
        $this->fakes();
        $svc = app(DteEmissionService::class);
        $dte = $svc->emitForSale($this->venta());

        $svc->invalidate($dte, '2', 'Operación rescindida');

        $this->assertSame('INVALIDADO', $dte->fresh()->estado);
        Http::assertSent(fn (Request $req) => $req->url() === self::URL_FIRMADOR && isset($req['dteJson']['motivo']['tipoAnulacion']) && $req['dteJson']['identificacion']['version'] === 3);
        Http::assertSent(fn (Request $req) => $req->url() === 'https://apitest.dtes.mh.gob.sv/fesv/anulardte'
            && $req['ambiente'] === '00' && $req['version'] === 3 && $req['documento'] === 'eyJhbGciOiJSUzUxMiJ9.dummy.firma');
        $this->assertSame(self::SELLO, DB::table('dte_invalidaciones')->where('dte_id', $dte->id)->value('respuesta_hacienda') ? json_decode(DB::table('dte_invalidaciones')->where('dte_id', $dte->id)->value('respuesta_hacienda'), true)['selloRecibido'] : null);
    }

    public function test_si_hacienda_rechaza_la_invalidacion_el_documento_sigue_vigente(): void
    {
        $this->fakes();
        $svc = app(DteEmissionService::class);
        $dte = $svc->emitForSale($this->venta());

        $this->fakes(['https://apitest.dtes.mh.gob.sv/fesv/anulardte' => Http::response(['estado' => 'RECHAZADO', 'codigoMsg' => '012', 'descripcionMsg' => 'DOCUMENTO SE ENCUENTRA INVALIDADO', 'observaciones' => ['Observación 1']], 400)]);

        try {
            $svc->invalidate($dte->fresh(), '2', 'x');
            $this->fail('Debió fallar');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('[012]', $e->getMessage());
        }
        $this->assertSame('ACEPTADO', $dte->fresh()->estado);
        $this->assertSame(0, DB::table('dte_invalidaciones')->count());
    }

    // ------------------------------------------------------------- contingencia: evento y lote

    private function contingencia(int $n = 2): array
    {
        $this->settings->update(['integration_mode' => 'contingencia']);
        $this->fakes();
        $svc = app(DteEmissionService::class);
        $dtes = [];
        for ($i = 0; $i < $n; $i++) {
            $dtes[] = $svc->emitForSale($this->venta());
        }
        $this->settings->update(['integration_mode' => 'real']);

        return $dtes;
    }

    public function test_en_contingencia_los_documentos_se_firman_de_verdad_si_hay_firmador(): void
    {
        [$dte] = $this->contingencia(1);

        $this->assertSame('CONTINGENCIA', $dte->estado);
        $this->assertSame('eyJhbGciOiJSUzUxMiJ9.dummy.firma', $dte->json_firmado['jsonFirmado']);
        $this->assertArrayNotHasKey('firmaSimulada', $dte->json_firmado);
        $this->assertSame(2, $dte->json_original['identificacion']['tipoOperacion']);
    }

    public function test_sin_firmador_la_contingencia_firma_en_simulacion_y_el_lote_real_lo_rechaza(): void
    {
        $this->settings->update(['integration_mode' => 'contingencia', 'signer_password' => null]);
        $this->fakes();
        $svc = app(DteEmissionService::class);
        $dte = $svc->emitForSale($this->venta());
        $this->assertArrayHasKey('firmaSimulada', $dte->json_firmado);
        $this->assertStringContainsString('Firma simulada', $dte->json_firmado['aviso']);

        // El evento también necesita firma real: sin la llave no se transmite.
        $this->settings->update(['integration_mode' => 'real']);
        $id = DB::table('dte_contingencias')->value('id');
        try {
            $svc->cerrarContingencia($id);
            $this->fail('Sin llave no se firma el evento');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('contraseña de la llave privada', $e->getMessage());
        }

        // Con la llave el evento pasa, pero el documento firmado en simulación no se manda en el lote.
        $this->settings->update(['signer_password' => 'llave-privada-dummy']);
        $this->assertSame('EVENTO_ENVIADO', $svc->cerrarContingencia($id)['estado']);
        $this->expectExceptionMessage('se firmó en simulación');
        $svc->enviarLoteContingencia($id);
    }

    public function test_evento_de_contingencia_real_y_lote_consultado_en_dos_tiempos(): void
    {
        $dtes = $this->contingencia(2);
        $id = DB::table('dte_contingencias')->value('id');
        $svc = app(DteEmissionService::class);

        // Momento 2: el evento viaja firmado a /fesv/contingencia.
        $r = $svc->cerrarContingencia($id);
        $this->assertSame('EVENTO_ENVIADO', $r['estado']);
        Http::assertSent(fn (Request $req) => $req->url() === 'https://apitest.dtes.mh.gob.sv/fesv/contingencia'
            && $req['nit'] === '05122808871013' && $req['documento'] === 'eyJhbGciOiJSUzUxMiJ9.dummy.firma');

        // Momento 3: el lote se recibe de inmediato pero se procesa después.
        $c1 = $dtes[0]->codigo_generacion;
        $c2 = $dtes[1]->codigo_generacion;
        $consultas = 0;
        $this->fakes([
            'https://apitest.dtes.mh.gob.sv/fesv/recepcionlote' => Http::response(['version' => 1, 'ambiente' => '00', 'versionApp' => 1, 'estado' => 'RECIBIDO', 'idEnvio' => 'X', 'codigoLote' => 'C86DD410-8BD7-48D3-8908-61B8C46AFE33', 'fhProcesamiento' => '20/11/2026 10:00:00', 'clasificaMsg' => '10', 'codigoMsg' => '001', 'descripcionMsg' => 'LOTE RECIBIDO']),
            'https://apitest.dtes.mh.gob.sv/fesv/recepcion/consultadtelote/*' => function () use (&$consultas, $c1, $c2) {
                $consultas++;
                // 1.ª consulta: solo uno procesado; 2.ª: el otro viene rechazado.
                return $consultas === 1
                    ? Http::response(['procesados' => [['codigoGeneracion' => $c1, 'selloRecibido' => self::SELLO, 'estado' => 'PROCESADO', 'index' => 0]], 'rechazados' => []])
                    : Http::response(['procesados' => [['codigoGeneracion' => $c1, 'selloRecibido' => self::SELLO]], 'rechazados' => [['codigoGeneracion' => $c2, 'estado' => 'RECHAZADO', 'codigoMsg' => '802', 'descripcionMsg' => 'No valido', 'observaciones' => ['campo x'], 'index' => 1]]]);
            },
        ]);

        $this->assertSame(1, $svc->enviarLoteContingencia($id), 'solo uno resuelto en la primera consulta');
        Http::assertSent(function (Request $req) {
            return $req->url() === 'https://apitest.dtes.mh.gob.sv/fesv/recepcionlote'
                && $req['ambiente'] === '00' && $req['version'] === 1 && $req['nitEmisor'] === '05122808871013'
                && count($req['documentos']) === 2 && $req['documentos'][0] === 'eyJhbGciOiJSUzUxMiJ9.dummy.firma'
                && preg_match('/^[A-F0-9-]{36}$/', $req['idEnvio']) === 1;
        });
        $this->assertSame(['ACEPTADO', 'CONTINGENCIA'], [$dtes[0]->fresh()->estado, $dtes[1]->fresh()->estado]);
        $this->assertSame('EVENTO_ENVIADO', DB::table('dte_contingencias')->find($id)->estado, 'sigue abierta: falta un documento');
        $this->assertSame('ENVIADO', DB::table('dte_lotes')->value('estado'));

        // Llamar de nuevo NO reenvía el lote: solo consulta.
        $this->assertSame(1, $svc->enviarLoteContingencia($id));
        $this->assertSame(1, Http::recorded(fn ($req) => str_contains($req->url(), 'recepcionlote'))->count(), 'el lote se envía una sola vez');
        $this->assertSame('RECHAZADO', $dtes[1]->fresh()->estado);
        $this->assertStringContainsString('[802]', $dtes[1]->fresh()->respuesta_hacienda['mensaje']);
        $this->assertSame('CERRADA', DB::table('dte_contingencias')->find($id)->estado);
        $this->assertSame('PROCESADO', DB::table('dte_lotes')->value('estado'));
    }

    public function test_un_lote_admite_como_maximo_100_documentos(): void
    {
        $this->fakes();
        $docs = array_fill(0, 101, ['jsonFirmado' => 'x', 'payload' => []]);

        $this->expectExceptionMessage('de 1 a 100');
        (new RealTransmisionService($this->auth()))->enviarLote($docs, $this->settings, '05122808871013', 'A1B2C3D4-E5F6-4789-9ABC-DEF012345678');
    }

    public function test_si_hacienda_no_recibe_el_lote_los_documentos_siguen_pendientes(): void
    {
        $dtes = $this->contingencia(1);
        $id = DB::table('dte_contingencias')->value('id');
        $svc = app(DteEmissionService::class);
        $svc->cerrarContingencia($id);

        $this->fakes(['https://apitest.dtes.mh.gob.sv/fesv/recepcionlote' => Http::response(['estado' => 'RECHAZADO', 'codigoMsg' => '027', 'descripcionMsg' => 'DOCUMENTO SIN SELLO'], 400)]);

        try {
            $svc->enviarLoteContingencia($id);
            $this->fail('El lote no recibido debe avisar');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('no recibió el lote', $e->getMessage());
        }
        $this->assertSame('CONTINGENCIA', $dtes[0]->fresh()->estado);
        $this->assertSame(0, DB::table('dte_lotes')->count());
    }

    // ------------------------------------------------------------- diagnóstico

    public function test_el_diagnostico_dice_que_falta_sin_credenciales_y_no_envia_nada(): void
    {
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->registerCommand($this->app->make(\Stockia\DteSvMh\Console\DiagnosticoConexionCommand::class));
        $this->settings->update(['api_user' => null, 'api_password' => null, 'signer_password' => null, 'responsable_nombre' => null, 'responsable_tipo_doc' => null, 'responsable_num_doc' => null]);
        Http::fake([self::URL_FIRMADOR.'status' => Http::response('Application is running...!!')]);

        $codigo = \Illuminate\Support\Facades\Artisan::call('dte:diagnostico-conexion');
        $salida = \Illuminate\Support\Facades\Artisan::output();

        $this->assertSame(1, $codigo);
        $this->assertSame(3, substr_count($salida, 'FALTA  |'), 'API, llave privada y responsable');
        $this->assertStringContainsString('por resolver', $salida);
        $this->assertStringNotContainsString('Dummy-Password', $salida);

        Http::assertNotSent(fn (Request $req) => str_contains($req->url(), 'dtes.mh.gob.sv'));
    }

    public function test_el_diagnostico_pasa_con_todo_configurado_y_prueba_autenticacion_y_firma(): void
    {
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->registerCommand($this->app->make(\Stockia\DteSvMh\Console\DiagnosticoConexionCommand::class));
        $this->fakes([self::URL_FIRMADOR.'status' => Http::response('Application is running...!!')]);

        $codigo = \Illuminate\Support\Facades\Artisan::call('dte:diagnostico-conexion', ['--autenticar' => true, '--firmar-prueba' => true]);
        $salida = \Illuminate\Support\Facades\Artisan::output();

        $this->assertSame(0, $codigo, $salida);
        $this->assertStringContainsString('Todo listo para transmitir', $salida);
        $this->assertStringContainsString('token obtenido', $salida);
        $this->assertStringContainsString('Firma de prueba', $salida);
    }
}
