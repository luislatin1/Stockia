<?php

namespace Stockia\DteSvMh\Console;

use App\Models\Company;
use App\Support\FiscalId;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Stockia\DteSvMh\Models\DteEstablecimiento;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Services\DteGatewayFactory;
use Stockia\DteSvMh\Services\Real\MhEndpoints;
use Stockia\DteSvMh\Services\Real\RealSigner;

/**
 * Revisa si una empresa está lista para transmitir de verdad: configuración, datos del emisor, códigos de
 * establecimiento, firmador local y (si se pide) autenticación y firma de prueba. No envía nada a Hacienda.
 * Sirve para saber QUÉ FALTA sin tener todavía credenciales: lo no configurado sale como FALTA, no como error.
 */
class DiagnosticoConexionCommand extends Command
{
    protected $signature = 'dte:diagnostico-conexion
        {--company_id= : empresa a revisar (por defecto la única con configuración DTE)}
        {--autenticar : además, pide un token a Hacienda con las credenciales guardadas}
        {--firmar-prueba : además, firma un documento de prueba con el firmador local}';

    protected $description = 'Revisa si la empresa está lista para transmitir a Hacienda (sin enviar documentos)';

    /** @var array<int, array{0:string,1:string,2:string,3:string}> */
    private array $filas = [];

    public function handle(DteGatewayFactory $fabrica): int
    {
        $settings = $this->configuracion();
        if (! $settings) {
            return self::FAILURE;
        }
        $company = Company::withoutGlobalScopes()->find($settings->company_id);
        $modo = $fabrica->mode($settings);

        $this->info("Empresa #{$settings->company_id} {$company?->name} · modo {$modo} · ambiente {$settings->ambiente}");

        // 1. Modo y URLs
        $this->fila('Modo', 'Integración', $modo === 'real' ? 'OK' : 'INFO', $modo === 'real' ? 'transmite a Hacienda' : "en modo {$modo} no se transmite; esta revisión es para cuando pase a Real");
        try {
            $this->fila('URL', 'Recepción', 'OK', MhEndpoints::recepcion($settings));
            $this->fila('URL', 'Autenticación', 'OK', MhEndpoints::auth($settings));
        } catch (\RuntimeException $e) {
            $this->fila('URL', 'Ambiente y servidor', 'ERROR', $e->getMessage());
        }

        // 2. Credenciales y secretos (nunca se muestran)
        $this->fila('Credenciales', 'Usuario y contraseña de la API', $settings->api_user && $settings->api_password ? 'OK' : 'FALTA', 'se piden a Hacienda al habilitar el ambiente');
        $this->fila('Credenciales', 'Contraseña de la llave privada (firmador)', $settings->signer_password ? 'OK' : 'FALTA', 'la del certificado de firma');
        $this->fila('Credenciales', 'Responsable de eventos', $settings->responsable_nombre && $settings->responsable_tipo_doc && $settings->responsable_num_doc ? 'OK' : 'FALTA', 'nombre y documento (invalidaciones y contingencia)');

        // 3. Emisor
        $this->revisarEmisor($company);
        $this->revisarEstablecimientos($settings);

        // 4. Firmador local
        $this->revisarFirmador($settings, (bool) $this->option('firmar-prueba'), $company);

        // 5. Autenticación opcional
        if ($this->option('autenticar')) {
            $this->probarAutenticacion($fabrica, $settings);
        }

        $this->table(['Área', 'Verificación', 'Estado', 'Detalle'], $this->filas);

        $faltan = count(array_filter($this->filas, fn ($f) => in_array($f[2], ['FALTA', 'ERROR'], true)));
        $this->newLine();
        $faltan === 0
            ? $this->info('Todo listo para transmitir.')
            : $this->warn("{$faltan} punto(s) por resolver antes de transmitir de verdad (lo marcado FALTA/ERROR).");

        return $faltan === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function fila(string $area, string $que, string $estado, string $detalle): void
    {
        $this->filas[] = [$area, $que, $estado, $detalle];
    }

    private function configuracion(): ?DteSetting
    {
        $q = DteSetting::query();
        if ($this->option('company_id')) {
            $q->where('company_id', (int) $this->option('company_id'));
        }
        $todas = $q->get();

        if ($todas->count() === 1) {
            return $todas->first();
        }
        $this->error($todas->isEmpty()
            ? 'No hay configuración DTE para esa empresa (DTE > Administración).'
            : 'Hay varias empresas con configuración DTE: use --company_id=ID.');

        return null;
    }

    private function revisarEmisor(?Company $c): void
    {
        if (! $c) {
            $this->fila('Emisor', 'Empresa', 'ERROR', 'no existe');

            return;
        }
        $faltan = [];
        FiscalId::nitDigits($c->nit ?: $c->tax_id) === null && $faltan[] = 'NIT válido';
        FiscalId::nrcDigits($c->nrc) === null && $faltan[] = 'NRC válido';
        foreach (['cod_actividad' => 'código de actividad', 'desc_actividad' => 'actividad económica', 'departamento' => 'departamento',
            'municipio' => 'municipio', 'distrito' => 'distrito', 'direccion_complemento' => 'dirección'] as $col => $nombre) {
            trim((string) $c->{$col}) === '' && $faltan[] = $nombre;
        }
        strlen(trim((string) ($c->telefono ?: $c->fiscal_phone))) < 8 && $faltan[] = 'teléfono';
        strlen(trim((string) ($c->correo ?: $c->fiscal_email))) < 6 && $faltan[] = 'correo';

        $this->fila('Emisor', 'Datos fiscales de la empresa', $faltan === [] ? 'OK' : 'FALTA', $faltan === [] ? 'completos' : implode(', ', $faltan).' (Administración Central)');
    }

    private function revisarEstablecimientos(DteSetting $settings): void
    {
        $estabs = DteEstablecimiento::withoutGlobalScopes()->where('company_id', $settings->company_id)->where('activo', true)->get();
        if ($estabs->isEmpty()) {
            $this->fila('Establecimientos', 'Establecimiento activo', 'FALTA', 'DTE > Establecimientos');

            return;
        }

        $sinPv = $estabs->filter(fn ($e) => ! $e->puntosVenta()->where('activo', true)->exists())->pluck('codigo')->all();
        $this->fila('Establecimientos', 'Punto de venta activo', $sinPv === [] ? 'OK' : 'FALTA', $sinPv === [] ? "{$estabs->count()} establecimiento(s)" : 'sin punto de venta: '.implode(', ', $sinPv));

        $sinConfirmar = $estabs->filter(function ($e) {
            return ! $e->codigo_confirmado || $e->puntosVenta()->where('activo', true)->where('codigo_confirmado', false)->exists();
        })->pluck('codigo')->all();
        $this->fila('Establecimientos', 'Códigos asignados por Hacienda', $sinConfirmar === [] ? 'OK' : 'FALTA', $sinConfirmar === []
            ? 'confirmados'
            : 'provisionales en '.implode(', ', $sinConfirmar).' (el modo real no emite con códigos sin confirmar)');
    }

    private function revisarFirmador(DteSetting $settings, bool $firmar, ?Company $company): void
    {
        $url = MhEndpoints::firmador($settings);
        try {
            $r = Http::timeout(4)->get($url.'status');
            $vivo = $r->successful() && str_contains(strtolower($r->body()), 'running');
            $this->fila('Firmador', 'Servicio local', $vivo ? 'OK' : 'ERROR', $vivo ? "responde en {$url}" : "responde HTTP {$r->status()} en {$url}");
        } catch (\Throwable) {
            $this->fila('Firmador', 'Servicio local', 'FALTA', "no responde en {$url} (instalar el firmador oficial: Docker o servicio de Windows)");

            return;
        }

        if (! $firmar) {
            return;
        }
        try {
            $nit = FiscalId::nitDigits($company?->nit ?: $company?->tax_id) ?? '00000000000000';
            $f = (new RealSigner())->sign(['identificacion' => ['codigoGeneracion' => 'PRUEBA'], 'emisor' => ['nit' => $nit]], new \App\Models\Sale(), $settings);
            $this->fila('Firmador', 'Firma de prueba', 'OK', 'JWS de '.strlen($f['jsonFirmado']).' caracteres');
        } catch (\RuntimeException $e) {
            $this->fila('Firmador', 'Firma de prueba', 'ERROR', $e->getMessage());
        }
    }

    private function probarAutenticacion(DteGatewayFactory $fabrica, DteSetting $settings): void
    {
        if (! $settings->api_user || ! $settings->api_password) {
            $this->fila('Autenticación', 'Token de Hacienda', 'FALTA', 'sin usuario/contraseña de API no se puede probar');

            return;
        }
        try {
            $r = $fabrica->auth($settings, 'real')->authenticate($settings);
            $this->fila('Autenticación', 'Token de Hacienda', 'OK', 'token obtenido (vigencia '.round(($r['body']['expiraEn'] ?? 0) / 3600).' h)');
        } catch (\RuntimeException $e) {
            $this->fila('Autenticación', 'Token de Hacienda', 'ERROR', $e->getMessage());
        }
    }
}
