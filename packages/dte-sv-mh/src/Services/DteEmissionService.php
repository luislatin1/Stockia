<?php

namespace Stockia\DteSvMh\Services;

use App\Models\Sale;
use App\Support\FiscalId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stockia\DteSvMh\Builders\DteV2Builder;
use Stockia\DteSvMh\Models\Dte;
use Stockia\DteSvMh\Models\DteInvalidation;
use Stockia\DteSvMh\Models\DteSetting;
use Stockia\DteSvMh\Schemas\DteSchemaValidator;
use Stockia\DteSvMh\Validators\ValidadorCoherenciaDte;
use Stockia\DteSvMh\Validators\ValidadorDocumentoSalvador;

class DteEmissionService
{
    public function __construct(private readonly DteGatewayFactory $factory)
    {
    }

    public function emitForSale(Sale $sale): ?Dte
    {
        if ($sale->document_type !== 'factura' || empty($sale->tipo_dte)) {
            return null;
        }

        $sale->loadMissing(['company', 'customer', 'items.product']);

        $existing = Dte::where('sale_id', $sale->id)->whereNull('dte_origen_id')->latest('id')->first();
        if ($existing && in_array((string) $existing->estado, ['PENDIENTE', 'ACEPTADO', 'CONTINGENCIA'], true)) {
            return $existing;
        }

        $settings = $this->resolveSettings((int) $sale->company_id);
        if (! $settings->enabled) {
            return null;
        }

        $mode = $this->factory->mode($settings);

        $emisor = app(DteEmitterResolver::class)->forSale($sale);

        // En modo real, los códigos de establecimiento y punto de venta deben
        // ser los asignados por Hacienda; en simulación/contingencia se permite
        // usar los provisionales.
        if ($mode === 'real' && (! $emisor['establecimiento']->codigo_confirmado || ! $emisor['punto_venta']->codigo_confirmado)) {
            throw new \RuntimeException(
                'Modo real: confirme con Hacienda los códigos de establecimiento '
                .$emisor['establecimiento']->codigo.' y punto de venta '.$emisor['punto_venta']->codigo
                .' en DTE > Establecimientos antes de emitir.'
            );
        }

        $correlative = app(DteNumberingService::class)->next(
            (int) $sale->company_id,
            $emisor['establecimiento'],
            $emisor['punto_venta'],
            (string) $sale->tipo_dte,
            (string) ($settings->numeracion_modo ?: DteNumberingService::MODO_CENTRALIZADA),
        );

        $codigoGeneracion = class_exists(\TuEmpresa\SvDte\Helpers\CodigoGeneracion::class)
            ? \TuEmpresa\SvDte\Helpers\CodigoGeneracion::generar()
            : strtoupper((string) Str::uuid());

        $payload = $this->buildPayload($sale, $settings, $correlative['numero_control'], $codigoGeneracion, $mode, $emisor);

        // Esquema oficial de Hacienda: si el documento no cumple, no se crea ni se firma.
        app(DteSchemaValidator::class)->asegurar($payload, (string) $sale->tipo_dte);

        $dte = Dte::create([
            'company_id' => $sale->company_id,
            'sale_id' => $sale->id,
            'tipo_dte' => $sale->tipo_dte,
            'codigo_generacion' => $codigoGeneracion,
            'numero_control' => $correlative['numero_control'],
            'json_original' => $payload,
            'estado' => 'PENDIENTE',
        ]);

        return $this->procesar($dte, $payload, $sale, $settings, $mode);
    }

    /** Contingencia o firma + transmisión + registro del intento. Común a todos los tipos de DTE. */
    private function procesar(Dte $dte, array $payload, Sale $sale, DteSetting $settings, string $mode): Dte
    {
        if ($mode === 'contingencia') {
            $dte->update([
                'estado' => 'CONTINGENCIA',
                'json_firmado' => [
                    'jsonFirmado' => base64_encode((string) json_encode($payload)),
                    'firmaSimulada' => hash('sha256', (string) json_encode($payload)),
                    'fechaFirma' => now()->toDateTimeString(),
                ],
                'respuesta_hacienda' => [
                    'estado' => 'CONTINGENCIA',
                    'mensaje' => 'DTE emitido en contingencia, pendiente de reenvío.',
                ],
            ]);

            DB::table('dte_contingencias')->updateOrInsert(
                [
                    'company_id' => $sale->company_id,
                    'tipo_contingencia' => 1,
                    'estado' => 'ABIERTA',
                ],
                [
                    'codigo_evento' => null,
                    'motivo' => 'Falla internet simulada',
                    'fecha_inicio' => now(),
                    'fecha_fin' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $contingenciaId = DB::table('dte_contingencias')
                ->where('company_id', $sale->company_id)
                ->where('estado', 'ABIERTA')
                ->latest('id')
                ->value('id');

            if ($contingenciaId) {
                DB::table('dte_contingencia_dtes')->updateOrInsert(
                    ['contingencia_id' => $contingenciaId, 'dte_id' => $dte->id],
                    ['reenviado' => false, 'updated_at' => now(), 'created_at' => now()]
                );
            }

            return $dte->fresh();
        }

        $signer = $this->factory->signer($settings);
        $sender = $this->factory->sender($settings);

        $signed = $signer->sign($payload, $sale, $settings);
        $sendResult = $sender->send($signed, $settings);

        DB::table('dte_send_attempts')->insert([
            'dte_id' => $dte->id,
            'attempt_no' => 1,
            'status' => (string) ($sendResult['status'] ?? 'ERROR'),
            'error_message' => $sendResult['error'] ?? null,
            'response_payload' => isset($sendResult['response']) ? json_encode($sendResult['response']) : null,
            'attempted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dte->update([
            'json_firmado' => $signed,
            'sello_recepcion' => $sendResult['sello'] ?? null,
            'respuesta_hacienda' => $sendResult['response'] ?? null,
            'estado' => $sendResult['status'] ?? 'RECHAZADO',
            'fecha_envio' => now(),
        ]);

        return $dte->fresh();
    }

    /**
     * Nota de Crédito (05) sobre un CCF aceptado, por devolución o ajuste de valor.
     *
     * @param  array<int, array{sale_item_id:int, cantidad:float, monto_neto:float}>  $lineas
     *         monto_neto = valor de la línea SIN IVA que se acredita (ya con descuento)
     */
    public function emitirNotaCredito(Sale $sale, Dte $origen, array $lineas, string $observaciones, ?int $adjustmentId = null): Dte
    {
        if ($lineas === []) {
            throw new \RuntimeException('La Nota de Crédito necesita al menos una línea.');
        }

        $sale->loadMissing(['company', 'items.product']);
        $itemsById = $sale->items->keyBy('id');
        $cuerpo = [];
        foreach ($lineas as $l) {
            $item = $itemsById->get((int) $l['sale_item_id']);
            if (! $item) {
                throw new \RuntimeException('Una línea no pertenece a la venta.');
            }
            $cantidad = (float) $l['cantidad'];
            $monto = round((float) $l['monto_neto'], 2);
            if ($cantidad <= 0 || $monto <= 0) {
                throw new \RuntimeException('Cada línea de la Nota de Crédito necesita cantidad y monto mayores a cero.');
            }
            $cuerpo[] = [
                'codigo' => $item->product->codigo ?? $item->product_id,
                'descripcion' => $item->product->name ?? 'ITEM',
                'cantidad' => $cantidad,
                'precio' => $monto / $cantidad,
                'descuento' => 0,
                'categoria' => (float) $item->monto_exento > 0 ? 'exenta' : ((float) $item->monto_no_sujeto > 0 ? 'no_sujeta' : 'gravada'),
                'tipo_item' => (int) ($item->tipo_item ?? 1),
                'uni_medida' => (int) ($item->uni_medida ?? 59),
            ];
        }

        return $this->emitirNotaSobreCcf('05', $sale, $origen, $cuerpo, $observaciones, $adjustmentId);
    }

    /**
     * Nota de Débito (06) sobre un CCF aceptado: cargos adicionales, intereses o diferencias de precio al alza.
     *
     * @param  array<int, array{descripcion:string, monto_neto:float, categoria?:string}>  $lineas
     *         monto_neto = valor del cargo SIN IVA; categoria gravada (por defecto) | exenta | no_sujeta
     */
    public function emitirNotaDebito(Sale $sale, Dte $origen, array $lineas, string $observaciones, ?int $adjustmentId = null): Dte
    {
        if ($lineas === []) {
            throw new \RuntimeException('La Nota de Débito necesita al menos un cargo.');
        }

        $cuerpo = [];
        foreach ($lineas as $l) {
            $monto = round((float) $l['monto_neto'], 2);
            $desc = trim((string) ($l['descripcion'] ?? ''));
            if ($monto <= 0 || $desc === '') {
                throw new \RuntimeException('Cada cargo de la Nota de Débito necesita descripción y un monto mayor a cero.');
            }
            $cuerpo[] = [
                'codigo' => null,
                'descripcion' => $desc,
                'cantidad' => 1.0,
                'precio' => $monto,
                'descuento' => 0,
                'categoria' => in_array(($l['categoria'] ?? 'gravada'), ['gravada', 'exenta', 'no_sujeta'], true) ? $l['categoria'] ?? 'gravada' : 'gravada',
                'tipo_item' => 2, // servicio (CAT-011): un cargo no es un bien
                'uni_medida' => 99, // otra (CAT-014)
            ];
        }

        return $this->emitirNotaSobreCcf('06', $sale, $origen, $cuerpo, $observaciones, $adjustmentId);
    }

    /** Parte común de NC (05) y ND (06): ambas son v4, se emiten sobre un CCF con sello y llevan el mismo receptor. */
    private function emitirNotaSobreCcf(string $tipo, Sale $sale, Dte $origen, array $cuerpo, string $observaciones, ?int $adjustmentId): Dte
    {
        $nombre = $tipo === '05' ? 'Nota de Crédito' : 'Nota de Débito';

        if ((string) $origen->tipo_dte !== '03') {
            throw new \RuntimeException("La {$nombre} solo se emite sobre un Comprobante de Crédito Fiscal (03).");
        }
        if ((string) $origen->estado !== 'ACEPTADO' || ! $origen->sello_recepcion) {
            throw new \RuntimeException("El CCF original debe estar ACEPTADO por Hacienda (con sello) para emitir una {$nombre}.");
        }

        $sale->loadMissing(['company']);
        $settings = $this->resolveSettings((int) $sale->company_id);
        if (! $settings->enabled) {
            throw new \RuntimeException("El módulo DTE está deshabilitado para esta empresa; no se puede emitir la {$nombre}.");
        }
        $mode = $this->factory->mode($settings);
        $emisorFiscal = app(DteEmitterResolver::class)->forSale($sale);

        if ($mode === 'real' && (! $emisorFiscal['establecimiento']->codigo_confirmado || ! $emisorFiscal['punto_venta']->codigo_confirmado)) {
            throw new \RuntimeException('Modo real: confirme con Hacienda los códigos de establecimiento y punto de venta antes de emitir.');
        }

        $orig = $origen->json_original;

        $r = $orig['receptor'] ?? null;
        if (! $r || empty($r['nit'])) {
            throw new \RuntimeException('El CCF original no tiene receptor con NIT.');
        }
        $receptor = [
            'tipoDocumento' => '36', 'numDocumento' => $r['nit'], 'nrc' => $r['nrc'] ?? null, 'nombre' => $r['nombre'],
            'codActividad' => $r['codActividad'], 'descActividad' => $r['descActividad'], 'nombreComercial' => $r['nombreComercial'] ?? null,
            'direccion' => $r['direccion'], 'telefono' => $r['telefono'] ?? null, 'correo' => $r['correo'] ?? null,
        ];

        $correlative = app(DteNumberingService::class)->next(
            (int) $sale->company_id,
            $emisorFiscal['establecimiento'],
            $emisorFiscal['punto_venta'],
            $tipo,
            (string) ($settings->numeracion_modo ?: DteNumberingService::MODO_CENTRALIZADA),
        );
        $codigoGeneracion = strtoupper((string) Str::uuid());
        $fecha = now(config('dte.timezone', 'America/El_Salvador'));

        $payload = (new DteV2Builder())->build([
            'tipo_dte' => $tipo,
            'ambiente' => (string) $settings->ambiente,
            'numero_control' => $correlative['numero_control'],
            'codigo_generacion' => $codigoGeneracion,
            'fecha' => $fecha,
            'contingencia' => $mode === 'contingencia'
                ? ['tipo' => 1, 'motivo' => 'Falla de conexión con el Ministerio de Hacienda']
                : null,
            'emisor' => $orig['emisor'],
            'receptor' => $receptor,
            'documentos_relacionados' => [[
                'tipo_documento' => '03', 'tipo_generacion' => 2,
                'numero' => $origen->codigo_generacion, 'fecha' => $orig['identificacion']['fecEmi'],
            ]],
            'items' => $cuerpo,
            'tasa_iva' => app(\App\Services\Dte\TaxRateService::class)->rate(\App\Services\Dte\TaxRateService::IVA, $fecha),
            'tasa_retencion_iva' => ((float) ($orig['resumen']['ivaRete'] ?? 0)) > 0 ? 0.01 : 0.0,
            'condicion_operacion' => (int) ($orig['resumen']['condicionOperacion'] ?? 1),
            'observaciones' => $observaciones,
        ]);

        app(DteSchemaValidator::class)->asegurar($payload, $tipo);

        $dte = Dte::create([
            'company_id' => $sale->company_id,
            'sale_id' => $sale->id,
            'dte_origen_id' => $origen->id,
            'sale_adjustment_id' => $adjustmentId,
            'tipo_dte' => $tipo,
            'codigo_generacion' => $codigoGeneracion,
            'numero_control' => $correlative['numero_control'],
            'json_original' => $payload,
            'estado' => 'PENDIENTE',
        ]);

        return $this->procesar($dte, $payload, $sale, $settings, $mode);
    }

    /**
     * Evento de Retorno (tipo 18) sobre una Factura aceptada: devolución parcial de bienes o reembolso.
     *
     * @param  array<int, array{sale_item_id:int, cantidad:float, monto_neto:float}>  $lineas
     */
    public function emitirEventoRetorno(Sale $sale, Dte $origen, array $lineas, ?int $adjustmentId = null): Dte
    {
        if ((string) $origen->tipo_dte !== '01') {
            throw new \RuntimeException('El Evento de Retorno solo se aplica a Facturas (01).');
        }
        if ((string) $origen->estado !== 'ACEPTADO' || ! $origen->sello_recepcion) {
            throw new \RuntimeException('La Factura original debe estar ACEPTADA por Hacienda (con sello) para aplicarle un Evento de Retorno.');
        }
        if ($lineas === []) {
            throw new \RuntimeException('El Evento de Retorno necesita al menos una línea.');
        }

        $sale->loadMissing(['company', 'items.product']);
        $settings = $this->resolveSettings((int) $sale->company_id);
        if (! $settings->enabled) {
            throw new \RuntimeException('El módulo DTE está deshabilitado para esta empresa; no se puede emitir el Evento de Retorno.');
        }
        $mode = $this->factory->mode($settings);
        $emisorFiscal = app(DteEmitterResolver::class)->forSale($sale);

        if ($mode === 'real' && (! $emisorFiscal['establecimiento']->codigo_confirmado || ! $emisorFiscal['punto_venta']->codigo_confirmado)) {
            throw new \RuntimeException('Modo real: confirme con Hacienda los códigos de establecimiento y punto de venta antes de emitir.');
        }

        $orig = $origen->json_original;
        $itemsById = $sale->items->keyBy('id');
        $cuerpo = [];
        foreach ($lineas as $l) {
            $item = $itemsById->get((int) $l['sale_item_id']);
            $cantidad = (float) $l['cantidad'];
            $monto = round((float) $l['monto_neto'], 2);
            if (! $item || $cantidad <= 0 || $monto <= 0) {
                throw new \RuntimeException('Cada línea del Evento de Retorno necesita un producto de la venta, cantidad y monto mayores a cero.');
            }
            $cuerpo[] = [
                'documento' => $origen->codigo_generacion,
                'codigo' => $item->product->codigo ?? $item->product_id,
                'descripcion' => $item->product->name ?? 'ITEM',
                'cantidad' => $cantidad,
                'precio' => $monto / $cantidad,
                'categoria' => (float) $item->monto_exento > 0 ? 'exenta' : ((float) $item->monto_no_sujeto > 0 ? 'no_sujeta' : 'gravada'),
                'tipo_item' => (int) ($item->tipo_item ?? 1),
                'uni_medida' => (int) ($item->uni_medida ?? 59),
            ];
        }

        // Receptor de la Factura original (null si era consumidor final sin identificar).
        $r = $orig['receptor'] ?? null;
        $documento = $r && ! empty($r['numDocumento']) ? [
            'tipoDocumento' => $r['tipoDocumento'] ?? null, 'numDocumento' => $r['numDocumento'], 'nombre' => $r['nombre'] ?? null,
            'codPais' => null, 'nombrePais' => null, 'telefono' => $r['telefono'] ?? null, 'correo' => $r['correo'] ?? null,
        ] : null;

        $e = $orig['emisor'];
        $estab = $emisorFiscal['establecimiento'];
        $pv = $emisorFiscal['punto_venta'];
        $fecha = now(config('dte.timezone', 'America/El_Salvador'));
        $codigoGeneracion = strtoupper((string) Str::uuid());

        $payload = (new \Stockia\DteSvMh\Builders\EventoRetornoBuilder())->build([
            'ambiente' => (string) $settings->ambiente,
            'codigo_generacion' => $codigoGeneracion,
            'fecha' => $fecha,
            'contingencia' => $mode === 'contingencia'
                ? ['tipo' => 1, 'motivo' => 'Falla de conexión con el Ministerio de Hacienda']
                : null,
            // Los eventos sí llevan el código asignado por Hacienda (MH) y el interno del contribuyente.
            'emisor' => [
                'nit' => $e['nit'], 'nombre' => $e['nombre'],
                'codEstableMH' => $estab->codigo, 'codEstable' => $e['codEstable'] ?? $estab->codigo,
                'codPuntoVentaMH' => $pv->codigo, 'codPuntoVenta' => $e['codPuntoVenta'] ?? $pv->codigo,
            ],
            'documento' => $documento,
            'relacionados' => [[
                'tipo_documento' => '01', 'codigo_generacion' => $origen->codigo_generacion, 'fecha' => $orig['identificacion']['fecEmi'],
            ]],
            'items' => $cuerpo,
            'tasa_iva' => app(\App\Services\Dte\TaxRateService::class)->rate(\App\Services\Dte\TaxRateService::IVA, $fecha),
        ]);

        app(DteSchemaValidator::class)->asegurar($payload, 'retorno');

        // Los eventos no tienen número de control; la columna es única y obligatoria, se rellena con una referencia propia.
        $dte = Dte::create([
            'company_id' => $sale->company_id,
            'sale_id' => $sale->id,
            'dte_origen_id' => $origen->id,
            'sale_adjustment_id' => $adjustmentId,
            'tipo_dte' => '18',
            'codigo_generacion' => $codigoGeneracion,
            'numero_control' => 'E18-'.$codigoGeneracion,
            'json_original' => $payload,
            'estado' => 'PENDIENTE',
        ]);

        return $this->procesar($dte, $payload, $sale, $settings, $mode);
    }

    public function resendContingency(?int $companyId = null): int
    {
        $query = Dte::query()->where('estado', 'CONTINGENCIA');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $dtes = $query->orderBy('id')->get();
        $processed = 0;

        foreach ($dtes as $dte) {
            $settings = $this->resolveSettings((int) $dte->company_id);
            $sender = $this->factory->sender($settings, 'simulacion');

            $signed = is_array($dte->json_firmado) ? $dte->json_firmado : ['payload' => $dte->json_original];
            if (! isset($signed['payload'])) {
                $signed['payload'] = $dte->json_original;
            }

            $sendResult = $sender->send($signed, $settings);
            $attemptNo = (int) DB::table('dte_send_attempts')->where('dte_id', $dte->id)->max('attempt_no') + 1;

            DB::table('dte_send_attempts')->insert([
                'dte_id' => $dte->id,
                'attempt_no' => $attemptNo,
                'status' => (string) ($sendResult['status'] ?? 'ERROR'),
                'error_message' => $sendResult['error'] ?? null,
                'response_payload' => isset($sendResult['response']) ? json_encode($sendResult['response']) : null,
                'attempted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $dte->update([
                'estado' => $sendResult['status'] ?? 'RECHAZADO',
                'sello_recepcion' => $sendResult['sello'] ?? null,
                'respuesta_hacienda' => $sendResult['response'] ?? null,
                'fecha_envio' => now(),
            ]);

            DB::table('dte_contingencia_dtes')
                ->where('dte_id', $dte->id)
                ->update(['reenviado' => true, 'reenviado_at' => now(), 'updated_at' => now()]);

            $processed++;
        }

        return $processed;
    }

    public function invalidate(Dte $dte, string $tipoInvalidacion, string $motivo, ?string $codigoGeneracionReemplazo = null): array
    {
        $settings = $this->resolveSettings((int) $dte->company_id);
        $mode = $this->factory->mode($settings);

        // Evento de Invalidación según el esquema oficial; si no lo cumple, no se transmite ni se marca nada.
        $evento = $this->construirEventoInvalidacion($dte, $settings, $mode, (int) $tipoInvalidacion, $motivo, $codigoGeneracionReemplazo);
        app(DteSchemaValidator::class)->asegurar($evento, 'invalidacion');

        $service = $this->factory->invalidation($settings);
        $response = $service->invalidate($dte, $settings, [
            'tipo_invalidacion' => $tipoInvalidacion,
            'motivo' => $motivo,
            'codigo_generacion_reemplazo' => $codigoGeneracionReemplazo,
            'evento' => $evento,
        ]);

        DteInvalidation::create([
            'dte_id' => $dte->id,
            'tipo_invalidacion' => $tipoInvalidacion,
            'motivo' => $motivo,
            'fecha_invalidacion' => now(),
            'estado_envio' => (string) ($response['estado'] ?? 'PENDIENTE'),
            'respuesta_hacienda' => $response,
            'json_evento' => $evento,
        ]);

        $dte->update(['estado' => 'INVALIDADO']);

        return $response;
    }

    /** Arma el Evento de Invalidación de cualquier DTE o evento propio a partir de su JSON original. */
    private function construirEventoInvalidacion(Dte $dte, DteSetting $settings, string $mode, int $tipo, string $motivo, ?string $reemplazo): array
    {
        $j = $dte->json_original;
        $tipoDte = (string) $dte->tipo_dte;
        $company = \App\Models\Company::withoutGlobalScopes()->find($dte->company_id);

        if (! $dte->sello_recepcion) {
            throw new \RuntimeException('El documento no tiene sello de recepción: no se puede invalidar.');
        }
        $sello = (string) $dte->sello_recepcion;
        if (strlen($sello) !== 40) {
            if ($mode === 'real') {
                throw new \RuntimeException('El sello de recepción debe tener 40 caracteres.');
            }
            // Simulación: los sellos de prueba son más cortos; se normalizan a 40 solo para armar el evento.
            $sello = strtoupper(sha1($sello));
        }

        // Códigos del establecimiento (Hacienda): del número de control en DTE; los eventos los traen en su emisor.
        if ($tipoDte === '18') {
            $estMH = $j['emisor']['codEstableMH'];
            $pvMH = $j['emisor']['codPuntoVentaMH'];
        } else {
            $estMH = substr((string) $dte->numero_control, 7, 4);
            $pvMH = substr((string) $dte->numero_control, 11, 4);
        }

        // Receptor del documento a invalidar, según la forma que tiene en cada esquema.
        $r = match ($tipoDte) {
            '18' => $j['documento'] ?? null,
            '03' => ! empty($j['receptor']['nit']) ? [
                'tipoDocumento' => '36', 'numDocumento' => $j['receptor']['nit'], 'nombre' => $j['receptor']['nombre'] ?? null,
                'telefono' => $j['receptor']['telefono'] ?? null, 'correo' => $j['receptor']['correo'] ?? null,
            ] : null,
            default => $j['receptor'] ?? null,
        };
        if ($r && empty($r['numDocumento'])) {
            $r = null;
        }

        // Quien realiza y quien solicita el evento (la misma persona, configurada por empresa).
        if ($settings->responsable_nombre && $settings->responsable_tipo_doc && $settings->responsable_num_doc) {
            $persona = ['nombre' => $settings->responsable_nombre, 'tipo_doc' => $settings->responsable_tipo_doc, 'num_doc' => $settings->responsable_num_doc];
        } elseif ($mode === 'real') {
            throw new \RuntimeException('Configure el responsable de eventos (nombre y documento) en DTE > Administración antes de invalidar en modo real.');
        } else {
            $persona = ['nombre' => 'RESPONSABLE DE PRUEBA (SIMULACION)', 'tipo_doc' => '13', 'num_doc' => '000000000'];
        }

        return (new \Stockia\DteSvMh\Builders\InvalidacionBuilder())->build([
            'ambiente' => (string) $settings->ambiente,
            'codigo_generacion' => strtoupper((string) Str::uuid()),
            'fecha' => now(config('dte.timezone', 'America/El_Salvador')),
            'emisor' => [
                'nit' => $j['emisor']['nit'], 'nombre' => $j['emisor']['nombre'],
                'codEstableMH' => $estMH, 'codEstable' => $j['emisor']['codEstable'] ?? null,
                'codPuntoVentaMH' => $pvMH, 'codPuntoVenta' => $j['emisor']['codPuntoVenta'] ?? null,
                'telefono' => $j['emisor']['telefono'] ?? ($company?->telefono ?: $company?->fiscal_phone),
                'correo' => $j['emisor']['correo'] ?? ($company?->correo ?: $company?->fiscal_email),
            ],
            'documento' => [
                'tipo_dte' => $tipoDte, 'codigo_generacion' => $dte->codigo_generacion, 'sello' => $sello,
                'numero_control' => $tipoDte === '18' ? null : $dte->numero_control,
                'fecha_emision' => $j['identificacion']['fecEmi'], 'reemplazo' => $reemplazo, 'receptor' => $r,
            ],
            'motivo' => ['tipo' => $tipo, 'texto' => $motivo, 'responsable' => $persona, 'solicita' => $persona],
        ]);
    }

    private function resolveSettings(int $companyId): DteSetting
    {
        $settings = DteSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'enabled' => true,
                'integration_mode' => config('dte.mode', 'simulacion'),
                'ambiente' => config('dte.ambiente', '00'),
                'api_user' => config('dte.api_user'),
                'api_password' => config('dte.api_password'),
                'auth_url' => config('dte.auth_url'),
                'send_url' => config('dte.send_url'),
                'signer_url' => config('dte.signer_url'),
                'use_dummy_certificate' => true,
                'dummy_certificate_text' => 'CERTIFICADO-DUMMY',
                'static_token' => 'TOKEN-DUMMY',
                'static_sello' => 'SELLO-DUMMY',
                'static_estado' => 'ACEPTADO',
            ]
        );

        if ($settings->integration_mode === 'static') {
            $settings->update(['integration_mode' => 'simulacion']);
            $settings->refresh();
        }

        return $settings;
    }

    private function buildPayload(
        Sale $sale,
        DteSetting $settings,
        string $numeroControl,
        string $codigoGeneracion,
        string $mode,
        array $emisorFiscal
    ): array {
        $company = $sale->company;
        $customer = $sale->customer;
        $tipo = (string) $sale->tipo_dte;
        $this->assertReceiverRules($sale, $customer);

        // El JSON del DTE exige NIT/NRC/documento SIN guiones (Normativa v2.0, Anexo IV).
        // En la base quedan con guiones; aquí se derivan, y si no son válidos se niega
        // la emisión con un mensaje claro en vez de enviar basura a Hacienda.
        $emisorNit = FiscalId::nitDigits($company->nit ?: $company->tax_id);
        if ($emisorNit === null) {
            throw new \RuntimeException("El NIT del emisor no es válido ('".($company->nit ?: $company->tax_id)."'): debe tener 14 dígitos (o 9). Corríjalo en Administración Central.");
        }

        $emisorNrc = FiscalId::nrcDigits($company->nrc);
        if ($emisorNrc === null) {
            throw new \RuntimeException("El NRC del emisor no es válido ('".$company->nrc."'): debe tener de 2 a 8 dígitos. Corríjalo en Administración Central.");
        }

        /** @var \Stockia\DteSvMh\Models\DteEstablecimiento $estab */
        $estab = $emisorFiscal['establecimiento'];
        /** @var \Stockia\DteSvMh\Models\DtePuntoVenta $pv */
        $pv = $emisorFiscal['punto_venta'];

        // La dirección del establecimiento manda si la tiene completa; si no, la de la empresa.
        $usaEstab = $estab->departamento && $estab->municipio && $estab->distrito && trim((string) $estab->direccion) !== '';
        $emisor = [
            'nit' => $emisorNit,
            'nrc' => $emisorNrc,
            'nombre' => $company->nombre_razon_social ?: $company->legal_name ?: $company->name,
            'codActividad' => $company->cod_actividad,
            'descActividad' => $company->desc_actividad,
            'nombreComercial' => $company->nombre_comercial ?: $company->name,
            'direccion' => [
                'departamento' => $usaEstab ? $estab->departamento : $company->departamento,
                'municipio' => $this->normalizeCodigo($usaEstab ? $estab->municipio : $company->municipio),
                'distrito' => $this->normalizeCodigo($usaEstab ? $estab->distrito : $company->distrito),
                'complemento' => $usaEstab ? $estab->direccion : ($company->direccion_complemento ?: $company->fiscal_address),
            ],
            'telefono' => $estab->telefono ?: ($company->telefono ?: $company->fiscal_phone),
            'correo' => $estab->correo ?: ($company->correo ?: $company->fiscal_email),
            // Códigos internos opcionales del emisor (no confundir con los de Hacienda del número de control).
            'codEstable' => $estab->codigo,
            'codPuntoVenta' => $pv->codigo,
        ];

        $items = $sale->items->values()->map(function ($item) {
            $categoria = (float) $item->monto_exento > 0 ? 'exenta'
                : ((float) $item->monto_no_sujeto > 0 ? 'no_sujeta' : 'gravada');

            return [
                'codigo' => $item->product->codigo ?? $item->product_id,
                'descripcion' => $item->product->name ?? 'ITEM',
                'cantidad' => (float) $item->quantity,
                'precio' => (float) $item->precio_unitario,
                'descuento' => (float) $item->descuento,
                'categoria' => $categoria,
                'tipo_item' => (int) ($item->tipo_item ?? 1),
                'uni_medida' => (int) ($item->uni_medida ?? 59),
            ];
        })->all();

        $fecha = now(config('dte.timezone', 'America/El_Salvador'));
        $esCredito = $sale->payment_method === 'credit' || (float) $sale->saldo_pendiente > 0;

        if ((float) $sale->retencion_renta > 0) {
            throw new \RuntimeException('La retención de renta aún no se transmite en Factura/CCF v2 (el esquema no la incluye).');
        }

        if ($tipo === '11') {
            return $this->payloadExportacion($sale, $settings, $numeroControl, $codigoGeneracion, $mode, $emisor, $items, $customer, $esCredito, $fecha);
        }

        return (new DteV2Builder())->build([
            'tipo_dte' => $tipo,
            'ambiente' => (string) $settings->ambiente,
            'numero_control' => $numeroControl,
            'codigo_generacion' => $codigoGeneracion,
            'fecha' => $fecha,
            'contingencia' => $mode === 'contingencia'
                ? ['tipo' => 1, 'motivo' => 'Falla de conexión con el Ministerio de Hacienda']
                : null,
            'emisor' => $emisor,
            'receptor' => $this->receptor($tipo, $customer),
            'items' => $items,
            'tasa_iva' => app(\App\Services\Dte\TaxRateService::class)->rate(\App\Services\Dte\TaxRateService::IVA, $fecha),
            'iva_retenido' => (float) $sale->retencion_iva,
            'condicion_operacion' => $esCredito ? 2 : 1,
            'forma_pago' => '01',
            'dias_credito' => (int) ($sale->dias_credito ?: 1),
        ]);
    }

    /** Receptor según el tipo: Factura admite consumidor final (null); CCF exige contribuyente completo. */
    private function receptor(string $tipo, $customer): ?array
    {
        if (! $customer) {
            return null; // consumidor final sin identificar (solo Factura; el CCF ya se rechazó antes)
        }

        $nrc = trim((string) $customer->nrc) !== '' ? FiscalId::nrcDigits($customer->nrc) : null;
        $tieneDireccion = $customer->departamento && $customer->municipio && $customer->distrito && trim((string) $customer->direccion) !== '';
        $direccion = $tieneDireccion ? [
            'departamento' => (string) $customer->departamento,
            'municipio' => $this->normalizeCodigo($customer->municipio),
            'distrito' => $this->normalizeCodigo($customer->distrito),
            'complemento' => (string) $customer->direccion,
        ] : null;

        $telefono = trim((string) $customer->telefono) !== '' ? $customer->telefono : null;
        $correo = trim((string) $customer->correo) !== '' ? $customer->correo : null;

        if ($tipo === '03') {
            // CCF v4: receptor completo, identificado por NIT (campo `nit`, no `tipoDocumento`).
            return [
                'nit' => FiscalId::nitDigits($customer->numero_documento),
                'nrc' => $nrc,
                'nombre' => $customer->nombre,
                'codActividad' => $customer->cod_actividad,
                'descActividad' => $customer->desc_actividad,
                'nombreComercial' => null,
                'direccion' => $direccion,
                'telefono' => $telefono,
                'correo' => $correo,
            ];
        }

        return [
            'tipoDocumento' => $customer->tipo_documento,
            'numDocumento' => FiscalId::documentoParaDte($customer->tipo_documento, $customer->numero_documento),
            'nrc' => $nrc,
            'nombre' => $customer->nombre ?: null,
            'codActividad' => $customer->cod_actividad ?: null,
            'descActividad' => $customer->desc_actividad ?: null,
            'direccion' => $direccion,
            'telefono' => $telefono,
            'correo' => $correo,
        ];
    }

    /** Descripción oficial de los INCOTERMS (CAT-031) para `descIncoterms`. */
    private const INCOTERMS = [
        '01' => 'EXW-En fabrica', '02' => 'FCA-Libre transportista', '03' => 'CPT-Transporte pagado hasta',
        '04' => 'CIP-Transporte y seguro pagado hasta', '05' => 'DAP-Entrega en el lugar', '06' => 'DPU-Entregado en el lugar descargado',
        '07' => 'DDP-Entrega con impuestos pagados', '08' => 'FAS-Libre al costado del buque', '09' => 'FOB-Libre a bordo',
        '10' => 'CFR-Costo y flete', '11' => 'CIF- Costo seguro y flete',
    ];

    /** Factura de Exportación (11): receptor del extranjero, IVA 0 % y datos de exportación de la empresa. */
    private function payloadExportacion(Sale $sale, DteSetting $settings, string $numeroControl, string $codigoGeneracion, string $mode, array $emisor, array $items, $customer, bool $esCredito, $fecha): array
    {
        if (! $settings->expo_tipo_item) {
            throw new \RuntimeException('Configure los datos de exportación (tipo, recinto, régimen) en DTE > Administración antes de emitir una factura de exportación.');
        }
        if (! $customer->cod_pais || ! $customer->nombre_pais || ! $customer->tipo_persona
            || trim((string) $customer->desc_actividad) === '' || trim((string) $customer->direccion) === '') {
            throw new \RuntimeException("El cliente '{$customer->nombre}' necesita país, tipo de persona, actividad económica y dirección para una exportación.");
        }
        if ((float) $sale->retencion_iva > 0 || (float) $sale->retencion_renta > 0) {
            throw new \RuntimeException('Las facturas de exportación no admiten retenciones.');
        }

        $emisor['tipoItemExpor'] = (int) $settings->expo_tipo_item;
        $emisor['recintoFiscal'] = $settings->expo_recinto;
        $emisor['tipoRegimen'] = $settings->expo_tipo_regimen;
        $emisor['regimen'] = $settings->expo_regimen;

        $telefono = trim((string) $customer->telefono);
        $correo = trim((string) $customer->correo);
        $inco = $settings->expo_cod_incoterms ?: null;

        return (new \Stockia\DteSvMh\Builders\ExportacionBuilder())->build([
            'ambiente' => (string) $settings->ambiente,
            'numero_control' => $numeroControl,
            'codigo_generacion' => $codigoGeneracion,
            'fecha' => $fecha,
            'contingencia' => $mode === 'contingencia'
                ? ['tipo' => 1, 'motivo' => 'Falla de conexión con el Ministerio de Hacienda']
                : null,
            'emisor' => $emisor,
            'receptor' => [
                'nombre' => $customer->nombre,
                'tipoDocumento' => (string) $customer->tipo_documento,
                'numDocumento' => (string) $customer->numero_documento,
                'descActividad' => $customer->desc_actividad,
                'nombreComercial' => null,
                'codPais' => strtoupper((string) $customer->cod_pais),
                'nombrePais' => $customer->nombre_pais,
                'complemento' => $customer->direccion,
                'tipoPersona' => (int) $customer->tipo_persona,
                'telefono' => strlen($telefono) >= 8 ? $telefono : null,
                'correo' => strlen($correo) >= 6 ? $correo : null,
            ],
            'items' => $items,
            'seguro' => 0.0,
            'flete' => 0.0,
            'condicion_operacion' => $esCredito ? 2 : 1,
            'forma_pago' => '01',
            'dias_credito' => (int) ($sale->dias_credito ?: 1),
            'cod_incoterms' => $inco,
            'desc_incoterms' => $inco ? (self::INCOTERMS[$inco] ?? null) : null,
        ]);
    }

    /**
     * Factura de Sujeto Excluido (14) por la compra a un proveedor que no es contribuyente de IVA.
     * El proveedor debe tener DUI o NIT válido y dirección completa (departamento, municipio, distrito).
     */
    public function emitirSujetoExcluido(\App\Models\Purchase $purchase, float $retencionRenta = 0.0, string $observaciones = ''): Dte
    {
        $purchase->loadMissing(['provider', 'items.product']);
        $prov = $purchase->provider;
        if (! $prov) {
            throw new \RuntimeException('La compra no tiene proveedor.');
        }

        $existente = Dte::withoutGlobalScopes()->where('purchase_id', $purchase->id)->whereIn('estado', ['PENDIENTE', 'ACEPTADO', 'CONTINGENCIA'])->exists();
        if ($existente) {
            throw new \RuntimeException('Esta compra ya tiene una Factura de Sujeto Excluido emitida.');
        }

        // Documento del vendedor: DUI preferido; si no, NIT. Sin guiones (Normativa v2.0).
        $dui = FiscalId::duiDigits($prov->dui);
        $nit = FiscalId::nitDigits($prov->nit);
        if ($dui !== null) {
            [$tipoDoc, $numDoc] = ['13', $dui];
        } elseif ($nit !== null) {
            [$tipoDoc, $numDoc] = ['36', $nit];
        } else {
            throw new \RuntimeException("El proveedor '{$prov->nombre}' necesita un DUI o NIT válido para emitir la factura de sujeto excluido.");
        }
        if (! $prov->departamento || ! $prov->municipio || ! $prov->distrito || trim((string) $prov->direccion) === '') {
            throw new \RuntimeException("El proveedor '{$prov->nombre}' necesita departamento, municipio, distrito y dirección completos.");
        }
        if (! \App\Support\UbicacionCatalogo::distritoValido($prov->departamento, $prov->municipio, $prov->distrito)) {
            throw new \RuntimeException('El distrito del proveedor no corresponde a su departamento/municipio.');
        }

        $company = \App\Models\Company::withoutGlobalScopes()->findOrFail($purchase->company_id);
        $settings = $this->resolveSettings((int) $company->id);
        if (! $settings->enabled) {
            throw new \RuntimeException('El módulo DTE está deshabilitado para esta empresa.');
        }
        $mode = $this->factory->mode($settings);
        $emisorFiscal = app(DteEmitterResolver::class)->forContext((int) $company->id, $purchase->warehouse_id ? (int) $purchase->warehouse_id : null);
        $estab = $emisorFiscal['establecimiento'];
        $pv = $emisorFiscal['punto_venta'];

        if ($mode === 'real' && (! $estab->codigo_confirmado || ! $pv->codigo_confirmado)) {
            throw new \RuntimeException('Modo real: confirme con Hacienda los códigos de establecimiento y punto de venta antes de emitir.');
        }

        $emisorNit = FiscalId::nitDigits($company->nit ?: $company->tax_id);
        $emisorNrc = FiscalId::nrcDigits($company->nrc);
        if ($emisorNit === null || $emisorNrc === null) {
            throw new \RuntimeException('El NIT y el NRC del emisor deben ser válidos (Administración Central).');
        }
        $usaEstab = $estab->departamento && $estab->municipio && $estab->distrito && trim((string) $estab->direccion) !== '';

        $items = $purchase->items->values()->map(fn ($i) => [
            'codigo' => $i->product->codigo ?? $i->product_id,
            'descripcion' => $i->product->name ?? 'ITEM',
            'cantidad' => (float) $i->cantidad,
            'precio' => (float) $i->costo_unitario,
            'descuento' => 0,
            'tipo_item' => 1,
            'uni_medida' => 59,
        ])->all();

        $correlative = app(DteNumberingService::class)->next(
            (int) $company->id, $estab, $pv, '14',
            (string) ($settings->numeracion_modo ?: DteNumberingService::MODO_CENTRALIZADA),
        );
        $codigoGeneracion = strtoupper((string) Str::uuid());

        $telefono = preg_replace('/\D+/', '', (string) $prov->telefono);
        $correo = trim((string) $prov->email);

        $payload = (new \Stockia\DteSvMh\Builders\SujetoExcluidoBuilder())->build([
            'ambiente' => (string) $settings->ambiente,
            'numero_control' => $correlative['numero_control'],
            'codigo_generacion' => $codigoGeneracion,
            'fecha' => now(config('dte.timezone', 'America/El_Salvador')),
            'contingencia' => $mode === 'contingencia'
                ? ['tipo' => 1, 'motivo' => 'Falla de conexión con el Ministerio de Hacienda']
                : null,
            'emisor' => [
                'nit' => $emisorNit, 'nrc' => $emisorNrc,
                'nombre' => $company->nombre_razon_social ?: $company->legal_name ?: $company->name,
                'codActividad' => $company->cod_actividad, 'descActividad' => $company->desc_actividad,
                'direccion' => [
                    'departamento' => $usaEstab ? $estab->departamento : $company->departamento,
                    'municipio' => $this->normalizeCodigo($usaEstab ? $estab->municipio : $company->municipio),
                    'distrito' => $this->normalizeCodigo($usaEstab ? $estab->distrito : $company->distrito),
                    'complemento' => $usaEstab ? $estab->direccion : ($company->direccion_complemento ?: $company->fiscal_address),
                ],
                'telefono' => $estab->telefono ?: ($company->telefono ?: $company->fiscal_phone),
                'correo' => $estab->correo ?: ($company->correo ?: $company->fiscal_email),
                'codEstable' => $estab->codigo, 'codPuntoVenta' => $pv->codigo,
            ],
            'receptor' => [
                'tipoDocumento' => $tipoDoc, 'numDocumento' => $numDoc, 'nombre' => $prov->nombre,
                'codActividad' => null, 'descActividad' => null,
                'direccion' => [
                    'departamento' => (string) $prov->departamento,
                    'municipio' => $this->normalizeCodigo($prov->municipio),
                    'distrito' => $this->normalizeCodigo($prov->distrito),
                    'complemento' => (string) $prov->direccion,
                ],
                'telefono' => strlen($telefono) >= 8 ? $telefono : null,
                'correo' => strlen($correo) >= 6 ? $correo : null,
            ],
            'items' => $items,
            'retencion_renta' => $retencionRenta,
            'condicion_operacion' => $purchase->es_credito ? 2 : 1,
            'forma_pago' => '01',
            'dias_credito' => (int) ($purchase->dias_credito ?: 1),
            'observaciones' => $observaciones,
        ]);

        app(DteSchemaValidator::class)->asegurar($payload, '14');

        $dte = Dte::create([
            'company_id' => $company->id,
            'sale_id' => null,
            'purchase_id' => $purchase->id,
            'tipo_dte' => '14',
            'codigo_generacion' => $codigoGeneracion,
            'numero_control' => $correlative['numero_control'],
            'json_original' => $payload,
            'estado' => 'PENDIENTE',
        ]);

        // La firma y el registro de intentos esperan una venta: se les pasa un contenedor con la empresa.
        $contexto = new Sale();
        $contexto->company_id = $company->id;
        $contexto->setRelation('company', $company);

        return $this->procesar($dte, $payload, $contexto, $settings, $mode);
    }

    private function assertReceiverRules(Sale $sale, $customer): void
    {
        $tipoDte = (string) $sale->tipo_dte;

        if ($tipoDte === '11' && ! $customer) {
            throw new \RuntimeException('La Factura de Exportación (11) requiere el cliente del extranjero.');
        }

        // Consumidor final sin identificar: el receptor va en null (permitido cuando el
        // monto es menor al legal). Antes se inventaba el DUI 00000000-0, que v2 rechaza.
        if ($customer) {
            $tipoDocumento = (string) $customer->tipo_documento;
            ValidadorDocumentoSalvador::validar($tipoDocumento, (string) $customer->numero_documento);
            ValidadorCoherenciaDte::validar($tipoDte, $tipoDocumento);

            if (trim((string) $customer->nrc) !== '') {
                ValidadorDocumentoSalvador::validarNrc((string) $customer->nrc);
            }
        }

        if ($tipoDte === '03') {
            if (! $customer) {
                throw new \RuntimeException('CCF (03) requiere cliente contribuyente.');
            }

            if ((string) $customer->tipo_documento !== '36') {
                throw new \RuntimeException('CCF (03) requiere NIT (36) como tipo de documento.');
            }

            if (trim((string) $customer->nrc) === '') {
                throw new \RuntimeException('CCF (03) requiere NRC del receptor.');
            }

            if (trim((string) $customer->cod_actividad) === '' || trim((string) $customer->desc_actividad) === '') {
                throw new \RuntimeException("CCF (03): falta la actividad económica del cliente '{$customer->nombre}'. Captúrela en DTE > Clientes.");
            }

            if (! $customer->departamento || ! $customer->municipio || ! $customer->distrito || trim((string) $customer->direccion) === '') {
                throw new \RuntimeException("CCF (03): el cliente '{$customer->nombre}' necesita departamento, municipio, distrito y dirección completos.");
            }
        }
    }

    private function normalizeCodigo(?string $municipio): ?string
    {
        if ($municipio === null || $municipio === '') {
            return $municipio;
        }

        $value = trim($municipio);
        return strlen($value) > 2 ? substr($value, -2) : str_pad($value, 2, '0', STR_PAD_LEFT);
    }
}
