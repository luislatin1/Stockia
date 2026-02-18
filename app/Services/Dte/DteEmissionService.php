<?php

namespace App\Services\Dte;

use App\Models\Dte;
use App\Models\DteInvalidation;
use App\Models\DteSetting;
use App\Models\Sale;
use App\Validators\ValidadorCoherenciaDte;
use App\Validators\ValidadorDocumentoSalvador;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $existing = Dte::where('sale_id', $sale->id)->latest('id')->first();
        if ($existing && in_array((string) $existing->estado, ['PENDIENTE', 'ACEPTADO', 'CONTINGENCIA'], true)) {
            return $existing;
        }

        $settings = $this->resolveSettings((int) $sale->company_id);
        if (! $settings->enabled) {
            return null;
        }

        $mode = $this->factory->mode($settings);

        $correlative = app(DteCorrelativeService::class)->next(
            (int) $sale->company_id,
            (string) $sale->tipo_dte,
            (string) $settings->establecimiento,
            (string) $settings->punto_venta
        );

        $codigoGeneracion = class_exists(\TuEmpresa\SvDte\Helpers\CodigoGeneracion::class)
            ? \TuEmpresa\SvDte\Helpers\CodigoGeneracion::generar()
            : strtoupper((string) Str::uuid());

        $payload = $this->buildPayload($sale, $settings, $correlative['numero_control'], $codigoGeneracion, $mode);

        if (class_exists(\TuEmpresa\SvDte\Validators\DummyValidator::class)) {
            \TuEmpresa\SvDte\Validators\DummyValidator::validate($payload);
        }

        $dte = Dte::create([
            'company_id' => $sale->company_id,
            'sale_id' => $sale->id,
            'tipo_dte' => $sale->tipo_dte,
            'codigo_generacion' => $codigoGeneracion,
            'numero_control' => $correlative['numero_control'],
            'json_original' => $payload,
            'estado' => 'PENDIENTE',
        ]);

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
            'response_payload' => $sendResult['response'] ?? null,
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
                'response_payload' => $sendResult['response'] ?? null,
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

    public function invalidate(Dte $dte, string $tipoInvalidacion, string $motivo): array
    {
        $settings = $this->resolveSettings((int) $dte->company_id);
        $service = $this->factory->invalidation($settings);
        $response = $service->invalidate($dte, $settings, [
            'tipo_invalidacion' => $tipoInvalidacion,
            'motivo' => $motivo,
        ]);

        DteInvalidation::create([
            'dte_id' => $dte->id,
            'tipo_invalidacion' => $tipoInvalidacion,
            'motivo' => $motivo,
            'fecha_invalidacion' => now(),
            'estado_envio' => (string) ($response['estado'] ?? 'PENDIENTE'),
            'respuesta_hacienda' => $response,
        ]);

        $dte->update(['estado' => 'INVALIDADO']);

        return $response;
    }

    private function resolveSettings(int $companyId): DteSetting
    {
        $settings = DteSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'enabled' => true,
                'integration_mode' => config('dte.mode', 'simulacion'),
                'ambiente' => config('dte.ambiente', '00'),
                'establecimiento' => '0001',
                'punto_venta' => '0001',
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
        string $mode
    ): array {
        $company = $sale->company;
        $customer = $sale->customer;
        $this->assertReceiverRules($sale, $customer);

        $items = $sale->items->values()->map(function ($item, $index) {
            return [
                'numItem' => $index + 1,
                'tipoItem' => (int) ($item->tipo_item ?? 1),
                'numeroDocumento' => null,
                'codigo' => $item->product->codigo ?? $item->product_id,
                'codTributo' => null,
                'descripcion' => $item->product->name ?? 'ITEM',
                'cantidad' => (float) $item->quantity,
                'uniMedida' => (int) ($item->uni_medida ?? 59),
                'precioUni' => (float) $item->precio_unitario,
                'montoDescu' => (float) $item->descuento,
                'ventaNoSuj' => (float) $item->monto_no_sujeto,
                'ventaExenta' => (float) $item->monto_exento,
                'ventaGravada' => (float) $item->monto_gravado,
                'tributos' => [],
                'psv' => 0,
                'noGravado' => 0,
            ];
        })->all();

        $input = [
            'numero_control' => $numeroControl,
            'codigo_generacion' => $codigoGeneracion,
            'emisor' => [
                'nit' => $company->nit ?: $company->tax_id,
                'nrc' => $company->nrc,
                'nombre' => $company->nombre_razon_social ?: $company->legal_name ?: $company->name,
                'nombreComercial' => $company->nombre_comercial ?: $company->name,
                'codActividad' => $company->cod_actividad,
                'descActividad' => $company->desc_actividad,
                'tipoEstablecimiento' => $company->tipo_establecimiento,
                'telefono' => $company->telefono ?: $company->fiscal_phone,
                'correo' => $company->correo ?: $company->fiscal_email,
                'direccion' => [
                    'departamento' => $company->departamento,
                    'municipio' => $company->municipio,
                    'complemento' => $company->direccion_complemento ?: $company->fiscal_address,
                ],
            ],
            'receptor' => [
                'tipoDocumento' => $customer?->tipo_documento ?: '13',
                'numDocumento' => $customer?->numero_documento ?: '00000000-0',
                'nrc' => $customer?->nrc,
                'nombre' => $customer?->nombre ?: 'Consumidor Final',
                'telefono' => $customer?->telefono,
                'correo' => $customer?->correo,
                'direccion' => [
                    'departamento' => $customer?->departamento,
                    'municipio' => $this->normalizeMunicipioCode($customer?->municipio),
                    'complemento' => $customer?->direccion,
                ],
            ],
            'items' => $items,
            'totales' => [
                'totalNoSuj' => (float) $sale->no_sujetas,
                'totalExenta' => (float) $sale->exentas,
                'totalGravada' => (float) $sale->gravadas,
                'subTotalVentas' => (float) $sale->subtotal,
                'descuNoSuj' => 0,
                'descuExenta' => 0,
                'descuGravada' => (float) $sale->descuento_total,
                'porcentajeDescuento' => 0,
                'totalDescu' => (float) $sale->descuento_total,
                'tributos' => [['codigo' => '20', 'descripcion' => 'IVA 13%', 'valor' => (float) $sale->iva]],
                'subTotal' => (float) $sale->subtotal,
                'ivaRete1' => (float) $sale->retencion_iva,
                'reteRenta' => (float) $sale->retencion_renta,
                'montoTotalOperacion' => (float) $sale->total,
                'totalPagar' => (float) $sale->total,
                'totalLetras' => 'TOTAL EN MODO DUMMY',
            ],
        ];

        if (class_exists(\TuEmpresa\SvDte\Builders\FacturaBuilder::class) && $sale->tipo_dte === '01') {
            $dte = (new \TuEmpresa\SvDte\Builders\FacturaBuilder())->make($input);
        } else {
            $dte = [
                'identificacion' => [
                    'version' => 1,
                    'ambiente' => $settings->ambiente,
                    'tipoDte' => $sale->tipo_dte,
                    'numeroControl' => $numeroControl,
                    'codigoGeneracion' => $codigoGeneracion,
                    'fecEmi' => now()->format('Y-m-d'),
                    'horEmi' => now()->format('H:i:s'),
                    'tipoMoneda' => 'USD',
                ],
                'emisor' => $input['emisor'],
                'receptor' => $input['receptor'],
                'cuerpoDocumento' => $items,
                'resumen' => $input['totales'],
            ];
        }

        if ($mode === 'contingencia') {
            $dte['identificacion']['tipoContingencia'] = 1;
            $dte['identificacion']['motivoContin'] = 'Falla internet simulada';
        }

        return $dte;
    }

    private function assertReceiverRules(Sale $sale, $customer): void
    {
        $tipoDte = (string) $sale->tipo_dte;
        $tipoDocumento = (string) ($customer?->tipo_documento ?: '13');
        $numeroDocumento = (string) ($customer?->numero_documento ?: '00000000-0');

        ValidadorDocumentoSalvador::validar($tipoDocumento, $numeroDocumento);
        ValidadorCoherenciaDte::validar($tipoDte, $tipoDocumento);

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
        }
    }

    private function normalizeMunicipioCode(?string $municipio): ?string
    {
        if ($municipio === null || $municipio === '') {
            return $municipio;
        }

        $value = trim($municipio);
        return strlen($value) > 2 ? substr($value, -2) : str_pad($value, 2, '0', STR_PAD_LEFT);
    }
}
