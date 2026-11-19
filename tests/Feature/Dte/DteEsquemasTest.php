<?php

namespace Tests\Feature\Dte;

use Carbon\Carbon;
use Stockia\DteSvMh\Schemas\DteSchemaException;
use Stockia\DteSvMh\Schemas\DteSchemaRegistry;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;
use Tests\TestCase;

/**
 * Esquemas oficiales de Hacienda: integridad, selección de versión y validación.
 */
class DteEsquemasTest extends TestCase
{
    private DteSchemaRegistry $registro;

    private DteSchemaValidator $validador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registro = new DteSchemaRegistry();
        $this->validador = new DteSchemaValidator($this->registro);
    }

    /** Factura v2 mínima y válida (la base del constructor de la Fase 2). */
    private function factura(array $cambios = []): array
    {
        $doc = [
            'identificacion' => [
                'version' => 2, 'ambiente' => '00', 'tipoDte' => '01',
                'numeroControl' => 'DTE-01-M001P001-000000000000001',
                'codigoGeneracion' => 'A1B2C3D4-E5F6-4789-9ABC-DEF012345678',
                'tipoModelo' => 1, 'tipoOperacion' => 1, 'tipoContingencia' => null, 'motivoContin' => null,
                'fecEmi' => '2026-11-19', 'horEmi' => '10:30:00', 'tipoMoneda' => 'USD',
            ],
            'documentoRelacionado' => null,
            'emisor' => [
                'nit' => '05122808871013', 'nrc' => '231194', 'nombre' => 'SUPER Y DISTRIBUIDORA QUEZALTECA',
                'codActividad' => '47112', 'descActividad' => 'Venta al por menor en comercios no especializados',
                'nombreComercial' => null,
                'direccion' => ['departamento' => '06', 'municipio' => '21', 'distrito' => '01', 'complemento' => 'Calle Principal #1'],
                'telefono' => '22223333', 'correo' => 'ventas@ejemplo.com.sv', 'codEstable' => null, 'codPuntoVenta' => null,
            ],
            'receptor' => [
                'tipoDocumento' => null, 'numDocumento' => null, 'nrc' => null, 'nombre' => null, 'codActividad' => null,
                'descActividad' => null, 'direccion' => null, 'telefono' => null, 'correo' => null,
            ],
            'otrosDocumentos' => null,
            'ventaTercero' => null,
            'cuerpoDocumento' => [[
                'numItem' => 1, 'tipoItem' => 1, 'numeroDocumento' => null, 'cantidad' => 1.0, 'codigo' => 'P1',
                'codTributo' => null, 'uniMedida' => 59, 'descripcion' => 'Producto de prueba', 'precioUni' => 113.0,
                'montoDescu' => 0.0, 'ventaNoSuj' => 0.0, 'ventaExenta' => 0.0, 'ventaGravada' => 113.0,
                'tributos' => null, 'psv' => 0.0, 'noGravado' => 0.0, 'ivaItem' => 13.0,
            ]],
            'resumen' => [
                'totalNoSuj' => 0.0, 'totalExenta' => 0.0, 'totalGravada' => 113.0, 'subTotalVentas' => 113.0,
                'descuNoSuj' => 0.0, 'descuExenta' => 0.0, 'descuGravada' => 0.0, 'porcentajeDescuento' => 0.0,
                'totalDescu' => 0.0, 'tributos' => [], 'subTotal' => 113.0, 'ivaRete' => 0.0,
                'montoTotalOperacion' => 113.0, 'totalNoGravado' => 0.0, 'totalPagar' => 113.0,
                'totalLetras' => 'CIENTO TRECE 00/100 USD', 'totalIva' => 13.0, 'saldoFavor' => 0.0, 'condicionOperacion' => 1,
                'pagos' => [['codigo' => '01', 'montoPago' => 113.0, 'referencia' => null, 'plazo' => null, 'periodo' => null]],
                'numPagoElectronico' => null, 'observaciones' => null,
            ],
            'apendice' => null,
        ];

        return array_replace_recursive($doc, $cambios);
    }

    private function rutas(array $errores): array
    {
        return array_column($errores, 'ruta');
    }

    // ---- Catálogo e integridad -------------------------------------------------------

    public function test_estan_los_15_esquemas_oficiales_y_su_integridad_es_correcta(): void
    {
        $this->assertCount(15, $this->registro->esquemas());
        $this->assertSame([], $this->registro->problemasDeIntegridad());
    }

    public function test_cada_esquema_de_documento_fija_su_tipo_y_su_version_igual_que_el_manifiesto(): void
    {
        foreach ($this->registro->esquemas() as $clave => $datos) {
            $clave = (string) $clave; // PHP vuelve enteras las claves numéricas sin cero inicial ('11' => 11)
            if ($datos['tipo'] !== 'documento') {
                continue;
            }
            foreach ($this->registro->versiones($clave) as $version) {
                $esquema = json_decode(file_get_contents($this->registro->ruta($clave, $version)), true);
                $ident = $esquema['properties']['identificacion']['properties'];

                $this->assertSame($clave, $ident['tipoDte']['const'], "tipoDte del esquema de $clave");
                $this->assertSame($version, $ident['version']['const'], "version del esquema de $clave");
            }
        }
    }

    public function test_versiones_oficiales_coinciden_con_las_del_documento_de_auditoria(): void
    {
        $esperadas = [
            '01' => 2, '03' => 4, '04' => 4, '05' => 4, '06' => 4, '07' => 2, '08' => 2, '09' => 2, '11' => 3,
            '14' => 2, '15' => 2, 'invalidacion' => 3, 'contingencia' => 4, 'retorno' => 1, 'operaciones_especiales' => 1,
        ];

        foreach ($esperadas as $clave => $version) {
            $this->assertSame($version, $this->registro->version($clave), "versión vigente de $clave");
        }
    }

    public function test_todos_los_esquemas_compilan_y_rechazan_un_documento_vacio_sin_excepciones(): void
    {
        foreach (array_keys($this->registro->esquemas()) as $clave) {
            $errores = $this->validador->validar([], $clave);
            $this->assertNotEmpty($errores, "El documento vacío debe ser inválido para $clave");
        }
    }

    // ---- Selección de versión (transición v1.2 -> v2.0) -------------------------------

    private function registroTemporal(?string $desdeV2): DteSchemaRegistry
    {
        $dir = sys_get_temp_dir().'/dte-esq-'.uniqid();
        mkdir($dir);
        foreach (['x-v1.json', 'x-v2.json'] as $f) {
            file_put_contents("$dir/$f", '{"type":"object"}');
        }
        file_put_contents("$dir/manifest.json", json_encode(['esquemas' => ['99' => [
            'tipo' => 'documento', 'nombre' => 'Prueba',
            'versiones' => [
                '1' => ['archivo' => 'x-v1.json', 'sha256' => 'x', 'vigente_desde' => null],
                '2' => ['archivo' => 'x-v2.json', 'sha256' => 'x', 'vigente_desde' => $desdeV2],
            ],
        ]]]));

        return new DteSchemaRegistry($dir);
    }

    public function test_la_version_nueva_entra_en_vigor_por_fecha_en_hora_de_el_salvador(): void
    {
        $r = $this->registroTemporal('2026-12-01');

        $this->assertSame(1, $r->version('99', null, Carbon::parse('2026-11-30 12:00', 'America/El_Salvador')));
        $this->assertSame(2, $r->version('99', null, Carbon::parse('2026-12-01 00:00', 'America/El_Salvador')));
        // 1-dic 03:00 UTC todavía es 30-nov en El Salvador (UTC-6).
        $this->assertSame(1, $r->version('99', null, Carbon::parse('2026-12-01 03:00', 'UTC')));
    }

    public function test_se_puede_forzar_una_version_por_argumento_o_por_configuracion(): void
    {
        $r = $this->registroTemporal(null);
        $this->assertSame(2, $r->version('99'));
        $this->assertSame(1, $r->version('99', 1));

        config(['dte.esquemas' => ['99' => 1]]);
        $this->assertSame(1, $r->version('99'));
        $this->assertSame(2, $r->version('99', 2), 'el argumento explícito gana sobre la configuración');
    }

    public function test_pedir_una_version_inexistente_falla_con_mensaje_claro(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no tiene la versión 7');
        $this->registroTemporal(null)->version('99', 7);
    }

    public function test_una_clave_desconocida_falla_listando_las_validas(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No hay esquema para');
        $this->registro->versiones('77');
    }

    // ---- Validación ---------------------------------------------------------------------

    public function test_una_factura_v2_correcta_pasa(): void
    {
        $this->assertSame([], $this->validador->validar($this->factura(), '01'));
    }

    public function test_el_numero_de_control_del_formato_viejo_es_rechazado(): void
    {
        // El formato que generaba Stockia antes de la Fase 1.
        $doc = $this->factura(['identificacion' => ['numeroControl' => 'DTE-01-0001-0001-000000000000001']]);

        $this->assertContains('/identificacion/numeroControl', $this->rutas($this->validador->validar($doc, '01')));
    }

    public function test_la_version_1_y_el_tipo_equivocado_son_rechazados(): void
    {
        $doc = $this->factura(['identificacion' => ['version' => 1, 'tipoDte' => '03']]);
        $rutas = $this->rutas($this->validador->validar($doc, '01'));

        $this->assertContains('/identificacion/version', $rutas);
        $this->assertContains('/identificacion/tipoDte', $rutas);
    }

    public function test_falta_el_distrito_del_emisor(): void
    {
        $doc = $this->factura();
        unset($doc['emisor']['direccion']['distrito']);

        $errores = $this->validador->validar($doc, '01');

        $this->assertContains('/emisor/direccion/distrito', $this->rutas($errores));
        $this->assertSame('campo requerido: falta', $errores[0]['mensaje']);
    }

    public function test_campos_que_v2_eliminó_son_rechazados(): void
    {
        $doc = $this->factura();
        $doc['resumen']['ivaRete1'] = 0.0;               // ahora es ivaRete
        $doc['resumen']['reteRenta'] = 0.0;              // ya no está en Factura
        $doc['emisor']['tipoEstablecimiento'] = '02';    // ya no existe en el emisor
        $doc['extension'] = null;                        // ya no existe (salvo Doc. Contable de Liquidación)

        $rutas = $this->rutas($this->validador->validar($doc, '01'));

        foreach (['/resumen/ivaRete1', '/resumen/reteRenta', '/emisor/tipoEstablecimiento', '/extension'] as $ruta) {
            $this->assertContains($ruta, $rutas);
        }
    }

    public function test_reporta_todos_los_errores_de_una_vez_y_en_espanol(): void
    {
        $doc = $this->factura([
            'identificacion' => ['ambiente' => '05', 'fecEmi' => '2026-13-45', 'horEmi' => '25:00:00'],
            'emisor' => ['nit' => '123456789012345', 'nrc' => '1'],
        ]);
        $doc['emisor']['nombre'] = 5;

        $errores = $this->validador->validar($doc, '01');
        $porRuta = array_column($errores, 'mensaje', 'ruta');

        $this->assertStringContainsString('no es uno de los valores permitidos', $porRuta['/identificacion/ambiente']);
        $this->assertStringContainsString('fecha válida', $porRuta['/identificacion/fecEmi']);
        $this->assertStringContainsString('formato esperado', $porRuta['/identificacion/horEmi']);
        $this->assertStringContainsString('longitud máxima 14', $porRuta['/emisor/nit']);
        $this->assertStringContainsString('longitud mínima 2', $porRuta['/emisor/nrc']);
        $this->assertStringContainsString('debe ser de tipo string', $porRuta['/emisor/nombre']);
        $this->assertGreaterThanOrEqual(6, count($errores));
    }

    public function test_los_numeros_negativos_donde_hay_minimo_son_rechazados(): void
    {
        $doc = $this->factura(['resumen' => ['totalPagar' => -1.0]]);

        $this->assertContains('/resumen/totalPagar', $this->rutas($this->validador->validar($doc, '01')));
    }

    public function test_asegurar_lanza_una_excepcion_con_el_detalle(): void
    {
        $doc = $this->factura(['identificacion' => ['numeroControl' => 'malo']]);

        try {
            $this->validador->asegurar($doc, '01');
            $this->fail('Debió lanzar DteSchemaException');
        } catch (DteSchemaException $e) {
            $this->assertSame('01', $e->clave);
            $this->assertSame(2, $e->version);
            $this->assertStringContainsString('/identificacion/numeroControl', $e->getMessage());
            $this->assertStringContainsString("esquema oficial '01' v2", $e->getMessage());
        }
    }

    public function test_un_entero_enviado_con_decimales_se_detecta(): void
    {
        // uniMedida es integer: 59.5 no es válido. Un float entero (59.0) sí cuenta como entero en JSON Schema.
        $malo = $this->factura();
        $malo['cuerpoDocumento'][0]['uniMedida'] = 59.5;
        $this->assertContains('/cuerpoDocumento/0/uniMedida', $this->rutas($this->validador->validar($malo, '01')));

        $bueno = $this->factura();
        $bueno['cuerpoDocumento'][0]['uniMedida'] = 59.0;
        $this->assertSame([], $this->validador->validar($bueno, '01'));
    }

    // ---- Comando ------------------------------------------------------------------------

    public function test_el_comando_lista_los_esquemas_y_confirma_la_integridad(): void
    {
        $this->artisan('dte:esquemas')
            ->expectsOutputToContain('Factura')
            ->expectsOutputToContain('Integridad: los 15 esquemas coinciden')
            ->assertSuccessful();
    }

    public function test_el_comando_valida_un_archivo_json(): void
    {
        $ruta = sys_get_temp_dir().'/factura-'.uniqid().'.json';
        file_put_contents($ruta, json_encode($this->factura(), JSON_PRESERVE_ZERO_FRACTION));
        $this->artisan('dte:esquemas', ['--validar' => $ruta])->expectsOutputToContain('Válido contra Factura')->assertSuccessful();

        $roto = $this->factura(['identificacion' => ['numeroControl' => 'x']]);
        file_put_contents($ruta, json_encode($roto, JSON_PRESERVE_ZERO_FRACTION));
        $this->artisan('dte:esquemas', ['--validar' => $ruta])->expectsOutputToContain('/identificacion/numeroControl')->assertFailed();

        unlink($ruta);
    }
}
