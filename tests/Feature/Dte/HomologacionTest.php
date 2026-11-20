<?php

namespace Tests\Feature\Dte;

use Illuminate\Support\Facades\File;
use Stockia\DteSvMh\Console\PruebasHomologacionCommand;
use Stockia\DteSvMh\Homologacion\GeneradorPruebas;
use Stockia\DteSvMh\Homologacion\VerificadorAritmetico;
use Stockia\DteSvMh\Schemas\DteSchemaRegistry;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;
use Tests\TestCase;

class HomologacionTest extends TestCase
{
    private function primero(string $clave, int $semilla = 2026): array
    {
        foreach ((new GeneradorPruebas($semilla))->generar($clave, 1) as $doc) {
            return $doc;
        }
    }

    public function test_los_minimos_coinciden_con_el_manual(): void
    {
        $c = GeneradorPruebas::CATALOGO;
        $this->assertSame([90, 75, 50, 25, 90, 25], [$c['01'][1], $c['03'][1], $c['05'][1], $c['06'][1], $c['11'][1], $c['14'][1]]);
        $this->assertSame([5, 5, 5, 5], [$c['invalidacion'][1], $c['contingencia'][1], $c['retorno'][1], $c['operaciones_especiales'][1]]);
        $this->assertSame(15, count($c));
    }

    public function test_cada_tipo_soportado_genera_documentos_validos_con_varias_semillas(): void
    {
        $validador = new DteSchemaValidator(new DteSchemaRegistry());

        foreach ([3, 2026] as $semilla) {
            foreach (GeneradorPruebas::CATALOGO as $clave => [$nombre, , $esquema]) {
                if ($esquema === null) {
                    continue;
                }
                foreach ((new GeneradorPruebas($semilla))->generar($clave, 8) as $i => $doc) {
                    $this->assertSame([], $validador->validar($doc, $esquema), "{$nombre} #{$i} (semilla {$semilla}) no cumple el esquema");
                    $this->assertSame([], VerificadorAritmetico::verificar($doc, $esquema), "{$nombre} #{$i} (semilla {$semilla}) no cuadra");
                }
            }
        }
    }

    public function test_la_misma_semilla_da_los_mismos_datos_y_otra_semilla_distintos(): void
    {
        $this->assertSame(
            json_encode($this->primero('03', 5)['cuerpoDocumento']),
            json_encode($this->primero('03', 5)['cuerpoDocumento'])
        );
        $this->assertNotSame(
            json_encode($this->primero('03', 5)['cuerpoDocumento']),
            json_encode($this->primero('03', 6)['cuerpoDocumento'])
        );
    }

    public function test_los_codigos_de_generacion_y_numeros_de_control_no_se_repiten(): void
    {
        $codigos = [];
        $controles = [];
        foreach ((new GeneradorPruebas(1))->generar('01', 90) as $doc) {
            $codigos[] = $doc['identificacion']['codigoGeneracion'];
            $controles[] = $doc['identificacion']['numeroControl'];
        }
        $this->assertCount(90, array_unique($codigos));
        $this->assertCount(90, array_unique($controles));
    }

    public function test_el_verificador_detecta_un_total_alterado(): void
    {
        foreach (['01' => 'totalPagar', '03' => 'totalPagar', '05' => 'totalGravada', '11' => 'totalPagar', '14' => 'totalPagar'] as $clave => $campo) {
            $doc = $this->primero($clave);
            $this->assertSame([], VerificadorAritmetico::verificar($doc, $clave));

            $doc['resumen'][$campo] = round($doc['resumen'][$campo] + 0.50, 2);
            $this->assertNotSame([], VerificadorAritmetico::verificar($doc, $clave), "tipo {$clave}: no detectó {$campo} alterado");
        }
    }

    public function test_el_verificador_detecta_un_item_y_un_iva_alterados(): void
    {
        $doc = $this->primero('01');
        $doc['cuerpoDocumento'][0]['ventaGravada'] = round($doc['cuerpoDocumento'][0]['ventaGravada'] + 1, 2);
        $this->assertNotSame([], VerificadorAritmetico::verificar($doc, '01'));

        $ccf = $this->primero('03');
        $ccf['resumen']['tributos'][0]['valor'] = round($ccf['resumen']['tributos'][0]['valor'] + 1, 2);
        $this->assertNotSame([], VerificadorAritmetico::verificar($ccf, '03'));
    }

    public function test_la_holgura_de_un_centavo_se_acepta(): void
    {
        $doc = $this->primero('01');
        $doc['resumen']['totalPagar'] = round($doc['resumen']['totalPagar'] + 0.01, 2);
        $this->assertSame([], VerificadorAritmetico::verificar($doc, '01'));
    }

    public function test_el_comando_pasa_y_exporta_los_json(): void
    {
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->registerCommand($this->app->make(PruebasHomologacionCommand::class));
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'homologacion_'.uniqid();

        $this->artisan('dte:pruebas-homologacion', ['--tipo' => ['01', 'retorno'], '--cantidad' => 4, '--exportar' => $dir])
            ->expectsOutputToContain('OK')
            ->assertExitCode(0);

        $this->assertCount(4, File::files("{$dir}/01"));
        $this->assertCount(4, File::files("{$dir}/retorno"));
        $resumen = json_decode(File::get("{$dir}/resumen.json"), true);
        $this->assertSame(4, $resumen['detalle']['01']['ok_esquema']);

        $doc = json_decode(File::get("{$dir}/01/001.json"), true);
        $this->assertSame('01', $doc['identificacion']['tipoDte']);
        File::deleteDirectory($dir);
    }

    public function test_el_comando_rechaza_tipos_sin_constructor(): void
    {
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->registerCommand($this->app->make(PruebasHomologacionCommand::class));

        $this->artisan('dte:pruebas-homologacion', ['--tipo' => ['07']])->expectsOutputToContain('No hay constructor')->assertExitCode(2);
    }
}
