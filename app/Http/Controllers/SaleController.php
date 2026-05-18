<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\NonSellableProduct;
use App\Models\CompanyUser;
use App\Models\Customer;
use App\Models\Dte;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Services\Dte\DteCalculationService;
use App\Services\Dte\DteEmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    private function currentContext(): array
    {
        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');

        if (! $companyId || ! $warehouseId) {
            abort(Response::HTTP_BAD_REQUEST, 'Empresa o almacén no seleccionado.');
        }

        return [$companyId, $warehouseId];
    }

    private function findSaleInContext(int $saleId, int $companyId, int $warehouseId): Sale
    {
        return Sale::where('id', $saleId)
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->firstOrFail();
    }

    private function isAdminRole(): bool
    {
        $role = function_exists('currentRole') ? currentRole() : null;

        return in_array($role, ['Admin', 'SuperAdmin'], true);
    }

    private function ensureSaleAccess(Sale $sale, int $companyId, int $warehouseId): void
    {
        if ((int) $sale->company_id !== $companyId || (int) $sale->warehouse_id !== $warehouseId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if (! $this->isAdminRole() && (int) $sale->user_id !== (int) auth()->id()) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }

    private function validateAdminPassword(?string $password, int $companyId): bool
    {
        if (! $password) {
            return false;
        }

        $adminUsers = CompanyUser::where('company_id', $companyId)
            ->whereIn('role', ['Admin', 'SuperAdmin'])
            ->with('user')
            ->get()
            ->pluck('user');

        foreach ($adminUsers as $user) {
            if ($user && Hash::check($password, $user->password)) {
                return true;
            }
        }

        return false;
    }

    public function index()
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $salesQuery = Sale::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId);

        if (! $this->isAdminRole()) {
            $salesQuery->where('user_id', auth()->id());
        }

        $sales = $salesQuery
            ->with('user')
            ->latest()
            ->get();

        return view('sales.index', compact('sales'));
    }

    public function exportExcel(): StreamedResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $salesQuery = Sale::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId);

        if (! $this->isAdminRole()) {
            $salesQuery->where('user_id', auth()->id());
        }

        $sales = $salesQuery
            ->with('user')
            ->latest('id')
            ->get();

        $filename = 'ventas_' . $companyId . '_' . $warehouseId . '_' . Date::now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($sales): void {
            $output = fopen('php://output', 'w');
            if (! $output) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Venta #',
                'Fecha',
                'Estado',
                'Documento',
                'Vendedor',
                'Subtotal',
                'IVA',
                'Total',
                'Efectivo',
                'Cambio',
            ], ';');

            foreach ($sales as $sale) {
                fputcsv($output, [
                    $sale->id,
                    optional($sale->created_at)->format('Y-m-d H:i:s'),
                    $sale->status,
                    $sale->document_type,
                    $sale->user->name ?? 'N/A',
                    number_format((float) ($sale->subtotal ?? 0), 2, '.', ''),
                    number_format((float) ($sale->tax_total ?? 0), 2, '.', ''),
                    number_format((float) ($sale->total ?? 0), 2, '.', ''),
                    number_format((float) ($sale->cash_received ?? 0), 2, '.', ''),
                    number_format((float) ($sale->change_amount ?? 0), 2, '.', ''),
                ], ';');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create()
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $products = Product::where('company_id', $companyId)
            ->whereHas('warehouses', function ($query) use ($warehouseId) {
                $query->where('warehouses.id', $warehouseId);
            })
            ->with(['warehouses' => function ($query) use ($warehouseId) {
                $query->where('warehouses.id', $warehouseId);
            }])
            ->get();

        $customers = Customer::where('company_id', $companyId)
            ->orderBy('nombre')
            ->limit(200)
            ->get(['id', 'nombre', 'tipo_documento', 'numero_documento']);

        return view('sales.create', compact('products', 'customers'));
    }

    public function show($id)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $sale = $this->findSaleInContext((int) $id, $companyId, $warehouseId);
        $this->ensureSaleAccess($sale, $companyId, $warehouseId);
        $sale->load(['items.product', 'company', 'warehouse', 'user', 'customer']);

        $adjustments = DB::table('sale_adjustments')
            ->leftJoin('users', 'users.id', '=', 'sale_adjustments.processed_by_user_id')
            ->where('sale_adjustments.sale_id', $sale->id)
            ->orderByDesc('sale_adjustments.id')
            ->get([
                'sale_adjustments.*',
                'users.name as processed_by_name',
            ]);

        $adjustmentItems = DB::table('sale_adjustment_items')
            ->leftJoin('products', 'products.id', '=', 'sale_adjustment_items.product_id')
            ->whereIn('sale_adjustment_items.sale_adjustment_id', $adjustments->pluck('id'))
            ->orderBy('sale_adjustment_items.id')
            ->get([
                'sale_adjustment_items.*',
                'products.name as product_name',
            ])
            ->groupBy('sale_adjustment_id');

        return view('sales.show', compact('sale', 'adjustments', 'adjustmentItems'));
    }

    public function edit(Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureSaleAccess($sale, $companyId, $warehouseId);

        if ($sale->status !== 'pending') {
            return redirect()->route('sales.index')
                ->withErrors('No se puede editar una venta completada o cancelada.');
        }

        return view('sales.edit', compact('sale'));
    }

    public function store(Request $request)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $validated = $request->validate([
            'products' => ['required', 'array'],
            'products.*' => ['nullable', 'integer', 'min:0'],
            'cash_received' => ['required', 'numeric', 'min:0'],
            'document_type' => ['required', 'in:ticket,factura'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'tipo_dte' => ['nullable', 'string', 'size:2'],
        ]);

        if (! empty($validated['customer_id'])) {
            $customer = Customer::where('id', (int) $validated['customer_id'])
                ->where('company_id', $companyId)
                ->first();
            if (! $customer) {
                return back()->withErrors('Cliente inválido para la empresa actual.');
            }
        }

        if (($validated['tipo_dte'] ?? null) === '03' && empty($validated['customer_id'])) {
            return back()->withErrors('Para CCF (03) debes seleccionar un cliente contribuyente.')->withInput();
        }

        $productsPayload = $validated['products'] ?? [];
        $hasItems = collect($productsPayload)->contains(fn ($qty) => (int) $qty > 0);

        if (! $hasItems) {
            return back()->withErrors('Debes agregar al menos un producto con cantidad mayor a cero.');
        }

        $requestedProductIds = collect($productsPayload)
            ->filter(fn ($qty) => (int) $qty > 0)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values();

        $availableCount = Product::where('company_id', $companyId)
            ->whereIn('id', $requestedProductIds)
            ->whereHas('warehouses', function ($query) use ($warehouseId) {
                $query->where('warehouses.id', $warehouseId);
            })
            ->count();

        if ($availableCount !== $requestedProductIds->count()) {
            return back()->withErrors('Uno o más productos no pertenecen a la empresa o al almacén actual.');
        }

        $products = Product::where('company_id', $companyId)
            ->whereIn('id', $requestedProductIds)
            ->get()
            ->keyBy('id');

        $lineItems = [];

        foreach ($productsPayload as $productId => $quantity) {
            $quantity = (int) $quantity;
            $productId = (int) $productId;

            if ($quantity <= 0) {
                continue;
            }

            $product = $products->get($productId);

            if (! $product) {
                return back()->withErrors('Uno o más productos no fueron encontrados.');
            }

            $lineItems[] = [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => (float) $product->price,
                'discount' => 0,
                'afecto_iva' => (bool) ($product->afecto_iva ?? true),
                'category' => (bool) ($product->afecto_iva ?? true) ? 'gravada' : 'exenta',
                'tipo_item' => (int) ($product->tipo_item ?? 1),
                'uni_medida' => isset($product->uni_medida) ? (int) $product->uni_medida : null,
                'product_id' => (int) $product->id,
            ];
        }

        $calculator = app(DteCalculationService::class);
        $calc = $calculator->calculate($lineItems, [
            'aplica_retencion_iva' => false,
            'retencion_renta' => 0,
        ]);
        $totals = $calc['totals'];
        $normalizedItems = $calc['items'];

        $subtotal = (float) $totals['subtotal'];
        $taxTotal = (float) $totals['iva'];
        $grandTotal = (float) $totals['total'];
        $cashReceived = round((float) $validated['cash_received'], 2);

        if ($cashReceived < $grandTotal) {
            return back()->withErrors('El efectivo recibido no cubre el total de la venta.');
        }

        $changeAmount = round($cashReceived - $grandTotal, 2);

        $saleId = DB::transaction(function () use (
            $products,
            $normalizedItems,
            $warehouseId,
            $companyId,
            $totals,
            $cashReceived,
            $changeAmount,
            $validated
        ) {
            $sale = Sale::create([
                'company_id' => $companyId,
                'customer_id' => $validated['customer_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'user_id' => auth()->id(),
                'status' => 'completed',
                'tipo_dte' => $validated['tipo_dte'] ?? $this->resolveTipoDte((string) $validated['document_type']),
                'numero_interno' => null,
                'gravadas' => $totals['gravadas'],
                'exentas' => $totals['exentas'],
                'no_sujetas' => $totals['no_sujetas'],
                'iva' => $totals['iva'],
                'retencion_iva' => $totals['retencion_iva'],
                'retencion_renta' => $totals['retencion_renta'],
                'descuento_total' => $totals['descuento_total'],
                'subtotal' => $totals['subtotal'],
                'tax_total' => $totals['iva'],
                'total' => $totals['total'],
                'payment_method' => 'cash',
                'cash_received' => $cashReceived,
                'change_amount' => $changeAmount,
                'document_type' => $validated['document_type'],
            ]);

            $sale->update([
                'numero_interno' => sprintf('V-%06d', (int) $sale->id),
            ]);

            foreach ($normalizedItems as $lineItem) {
                $product = $products->get((int) $lineItem['product_id']);
                if (! $product) {
                    throw new \RuntimeException('Producto inválido en detalle de venta.');
                }
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $lineItem['quantity'],
                    'price' => $lineItem['price'],
                    'precio_unitario' => $lineItem['precio_unitario'],
                    'descuento' => $lineItem['descuento'],
                    'monto_gravado' => $lineItem['monto_gravado'],
                    'monto_exento' => $lineItem['monto_exento'],
                    'monto_no_sujeto' => $lineItem['monto_no_sujeto'],
                    'iva_item' => $lineItem['iva_item'],
                    'total_item' => $lineItem['total_item'],
                    'tipo_item' => $lineItem['tipo_item'],
                    'uni_medida' => $lineItem['uni_medida'],
                    'subtotal' => $lineItem['subtotal'],
                ]);

                InventoryMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $lineItem['quantity'],
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'user_id' => auth()->id(),
                ]);
            }

            return (int) $sale->id;
        });

        $warning = null;
        try {
            $sale = Sale::find($saleId);
            if ($sale) {
                app(DteEmissionService::class)->emitForSale($sale);
            }
        } catch (\Throwable $e) {
            $warning = 'Venta guardada, pero DTE no emitido: ' . $e->getMessage();
        }

        $redirect = redirect()->route('sales.index')
            ->with('success', 'Venta registrada correctamente');

        if ($warning) {
            $redirect->with('warning', $warning);
        }

        return $redirect;
    }

    public function ticket(Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureSaleAccess($sale, $companyId, $warehouseId);

        $sale->load(['items.product', 'company', 'warehouse', 'user']);

        return view('sales.ticket', compact('sale'));
    }

    public function invoice(Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureSaleAccess($sale, $companyId, $warehouseId);

        $sale->load(['items.product', 'company', 'warehouse', 'user']);

        return view('sales.invoice', compact('sale'));
    }

    public function ticketPdf(Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureSaleAccess($sale, $companyId, $warehouseId);

        $sale->load(['items.product', 'company', 'warehouse', 'user']);

        return $this->streamSalePdf(
            'sales.pdf-ticket',
            $sale,
            'ticket-venta-' . $sale->id . '.pdf',
            route('sales.ticket', $sale)
        );
    }

    public function invoicePdf(Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureSaleAccess($sale, $companyId, $warehouseId);

        $sale->load(['items.product', 'company', 'warehouse', 'user']);
        $dteData = $this->buildDteTemplateData($sale);

        return $this->streamSalePdf(
            'sales.pdf-dte',
            $sale,
            'factura-venta-' . $sale->id . '.pdf',
            route('sales.invoice', $sale),
            ['dteData' => $dteData]
        );
    }

    private function streamSalePdf(string $view, Sale $sale, string $filename, string $fallbackRoute, array $viewData = [])
    {
        if (! app()->bound('dompdf.wrapper')) {
            return redirect($fallbackRoute)
                ->withErrors('PDF no disponible: instala barryvdh/laravel-dompdf en el CORE.');
        }

        $pdf = app('dompdf.wrapper');
        if (method_exists($pdf, 'setOption')) {
            $pdf->setOption(['isRemoteEnabled' => true]);
        }

        $pdf->loadView($view, array_merge(['sale' => $sale], $viewData));

        return $pdf->stream($filename);
    }

    private function buildDteTemplateData(Sale $sale): array
    {
        $dte = Dte::where('sale_id', $sale->id)->latest('id')->first();
        $payload = is_array($dte?->json_original) ? $dte->json_original : [];
        $identificacion = is_array($payload['identificacion'] ?? null) ? $payload['identificacion'] : [];
        $emisor = is_array($payload['emisor'] ?? null) ? $payload['emisor'] : [];
        $receptor = is_array($payload['receptor'] ?? null) ? $payload['receptor'] : [];
        $resumen = is_array($payload['resumen'] ?? null) ? $payload['resumen'] : [];

        $detalle = $payload['cuerpoDocumento'] ?? [];
        if (! is_array($detalle) || $detalle === []) {
            $detalle = $sale->items->values()->map(function ($item, $index) {
                return [
                    'numItem' => $index + 1,
                    'cantidad' => (float) ($item->quantity ?? 0),
                    'uniMedida' => (string) ($item->uni_medida ?? '59'),
                    'descripcion' => (string) ($item->product->name ?? 'ITEM'),
                    'precioUni' => (float) ($item->precio_unitario ?? $item->price ?? 0),
                    'montoDescu' => (float) ($item->descuento ?? 0),
                    'ventaGravada' => (float) ($item->monto_gravado ?? $item->subtotal ?? 0),
                    'ventaExenta' => (float) ($item->monto_exento ?? 0),
                    'ventaNoSuj' => (float) ($item->monto_no_sujeto ?? 0),
                ];
            })->all();
        }

        $tipoDte = (string) ($identificacion['tipoDte'] ?? $sale->tipo_dte ?? $dte?->tipo_dte ?? '');
        $numeroControl = (string) ($identificacion['numeroControl'] ?? $dte?->numero_control ?? 'N/D');
        $codigoGeneracion = (string) ($identificacion['codigoGeneracion'] ?? $dte?->codigo_generacion ?? 'N/D');
        $selloRecepcion = (string) ($dte?->sello_recepcion ?? 'N/D');
        $fechaEmision = (string) ($identificacion['fecEmi'] ?? optional($sale->created_at)->format('Y-m-d'));
        $horaEmision = (string) ($identificacion['horEmi'] ?? optional($sale->created_at)->format('H:i:s'));
        $ambienteCode = (string) ($identificacion['ambiente'] ?? '00');
        $ambienteLabel = $this->mapAmbienteLabel($ambienteCode);
        $condicionOperacion = (string) ($resumen['condicionOperacion'] ?? $this->resolveCondicionOperacion($sale));
        $condicionLabel = $this->mapCondicionOperacionLabel($condicionOperacion);

        $qrPublicUrl = $this->buildMhPublicQrUrl($ambienteCode, $codigoGeneracion, $fechaEmision);
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($qrPublicUrl);

        $referencias = $payload['documentoRelacionado'] ?? $payload['documentosRelacionados'] ?? [];
        if (is_array($referencias) && array_key_exists('tipoDocumento', $referencias)) {
            $referencias = [$referencias];
        }
        if (! is_array($referencias)) {
            $referencias = [];
        }

        $motivo = (string) (
            $payload['motivo'] ?? $payload['motivoNota'] ?? $payload['extension']['observaciones'] ?? ''
        );

        return [
            'tipo_dte' => $tipoDte,
            'tipo_dte_label' => $this->mapTipoDteLabel($tipoDte),
            'numero_control' => $numeroControl,
            'codigo_generacion' => $codigoGeneracion,
            'sello_recepcion' => $selloRecepcion,
            'fecha_emision' => $fechaEmision,
            'hora_emision' => $horaEmision,
            'ambiente_code' => $ambienteCode,
            'ambiente_label' => $ambienteLabel,
            'condicion_operacion' => $condicionOperacion,
            'condicion_operacion_label' => $condicionLabel,
            'emisor' => $this->buildEmisorData($sale, $emisor),
            'receptor' => $this->buildReceptorData($sale, $receptor),
            'detalle' => $detalle,
            'resumen' => $this->buildResumenData($sale, $resumen),
            'referencias' => $referencias,
            'motivo' => $motivo,
            'firma_digital' => (string) ($dte?->json_firmado['firmaSimulada'] ?? ''),
            'periodo_fiscal' => (string) ($payload['periodoTributario'] ?? $payload['periodoFiscal'] ?? ''),
            'qr_public_url' => $qrPublicUrl,
            'qr_image_url' => $qrImageUrl,
        ];
    }

    private function buildEmisorData(Sale $sale, array $emisor): array
    {
        return [
            'nombre' => (string) ($emisor['nombre'] ?? $sale->company->nombre_razon_social ?? $sale->company->legal_name ?? $sale->company->name ?? 'N/D'),
            'nit' => (string) ($emisor['nit'] ?? $sale->company->nit ?? $sale->company->tax_id ?? 'N/D'),
            'nrc' => (string) ($emisor['nrc'] ?? $sale->company->nrc ?? 'N/D'),
            'codActividad' => (string) ($emisor['codActividad'] ?? $sale->company->cod_actividad ?? 'N/D'),
            'descActividad' => (string) ($emisor['descActividad'] ?? $sale->company->desc_actividad ?? 'N/D'),
            'direccion' => (string) ($emisor['direccion']['complemento'] ?? $sale->company->direccion_complemento ?? $sale->company->fiscal_address ?? 'N/D'),
            'telefono' => (string) ($emisor['telefono'] ?? $sale->company->telefono ?? $sale->company->fiscal_phone ?? 'N/D'),
            'correo' => (string) ($emisor['correo'] ?? $sale->company->correo ?? $sale->company->fiscal_email ?? 'N/D'),
        ];
    }

    private function buildReceptorData(Sale $sale, array $receptor): array
    {
        return [
            'nombre' => (string) ($receptor['nombre'] ?? $sale->customer->nombre ?? 'Consumidor Final'),
            'tipoDocumento' => (string) ($receptor['tipoDocumento'] ?? $sale->customer->tipo_documento ?? 'N/D'),
            'numDocumento' => (string) ($receptor['numDocumento'] ?? $sale->customer->numero_documento ?? 'N/D'),
            'nrc' => (string) ($receptor['nrc'] ?? $sale->customer->nrc ?? 'N/D'),
            'direccion' => (string) ($receptor['direccion']['complemento'] ?? $sale->customer->direccion ?? 'N/D'),
            'telefono' => (string) ($receptor['telefono'] ?? $sale->customer->telefono ?? 'N/D'),
            'correo' => (string) ($receptor['correo'] ?? $sale->customer->correo ?? 'N/D'),
        ];
    }

    private function buildResumenData(Sale $sale, array $resumen): array
    {
        $tributos = is_array($resumen['tributos'] ?? null) ? $resumen['tributos'] : [];
        $ivaTributo = is_array($tributos[0] ?? null) ? (float) ($tributos[0]['valor'] ?? 0) : 0.0;

        return [
            'subTotal' => (float) ($resumen['subTotal'] ?? $sale->subtotal ?? 0),
            'totalDescu' => (float) ($resumen['totalDescu'] ?? $sale->descuento_total ?? 0),
            'totalNoSuj' => (float) ($resumen['totalNoSuj'] ?? $sale->no_sujetas ?? 0),
            'totalExenta' => (float) ($resumen['totalExenta'] ?? $sale->exentas ?? 0),
            'totalGravada' => (float) ($resumen['totalGravada'] ?? $sale->gravadas ?? 0),
            'iva' => (float) ($ivaTributo ?: $sale->iva ?: $sale->tax_total ?: 0),
            'ivaRete1' => (float) ($resumen['ivaRete1'] ?? $sale->retencion_iva ?? 0),
            'reteRenta' => (float) ($resumen['reteRenta'] ?? $sale->retencion_renta ?? 0),
            'montoTotalOperacion' => (float) ($resumen['montoTotalOperacion'] ?? $sale->total ?? 0),
            'totalPagar' => (float) ($resumen['totalPagar'] ?? $sale->total ?? 0),
            'condicionOperacion' => (string) ($resumen['condicionOperacion'] ?? $this->resolveCondicionOperacion($sale)),
        ];
    }

    private function buildMhPublicQrUrl(string $ambiente, string $codigoGeneracion, string $fechaEmision): string
    {
        return 'https://admin.factura.gob.sv/consultaPublica?ambiente='
            . rawurlencode($ambiente)
            . '&codGen=' . rawurlencode($codigoGeneracion)
            . '&fechaEmi=' . rawurlencode($fechaEmision);
    }

    private function resolveCondicionOperacion(Sale $sale): string
    {
        return ((string) ($sale->payment_method ?? 'cash')) === 'cash' ? '1' : '2';
    }

    private function mapCondicionOperacionLabel(string $condicion): string
    {
        return match ($condicion) {
            '1' => 'Contado',
            '2' => 'Crédito',
            '3' => 'Otro',
            default => 'N/D',
        };
    }

    private function mapAmbienteLabel(string $ambiente): string
    {
        return match ($ambiente) {
            '01' => 'Producción',
            '00' => 'Pruebas',
            default => 'N/D',
        };
    }

    private function mapTipoDteLabel(string $tipoDte): string
    {
        return match ($tipoDte) {
            '01' => 'FACTURA ELECTRÓNICA',
            '03' => 'COMPROBANTE DE CRÉDITO FISCAL ELECTRÓNICO',
            '05' => 'NOTA DE CRÉDITO ELECTRÓNICA',
            '06' => 'NOTA DE DÉBITO ELECTRÓNICA',
            '07' => 'COMPROBANTE DE RETENCIÓN ELECTRÓNICO',
            '08' => 'COMPROBANTE DE LIQUIDACIÓN ELECTRÓNICO',
            default => 'DOCUMENTO TRIBUTARIO ELECTRÓNICO',
        };
    }

    public function adminAdjustment(Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureSaleAccess($sale, $companyId, $warehouseId);
        $sale->load(['items.product', 'company', 'warehouse', 'user']);

        return view('sales.admin-adjustment', compact('sale'));
    }

    public function storeAdminAdjustment(Request $request, Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureSaleAccess($sale, $companyId, $warehouseId);
        $sale->load(['items.product']);

        $validated = $request->validate([
            'action_type' => ['required', 'in:cancel_sale,void_invoice'],
            'refund_cash' => ['nullable', 'boolean'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'return_products' => ['nullable', 'boolean'],
            'admin_password' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array'],
            'items.*.sale_item_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.condition' => ['nullable', 'in:unopened,damaged,expired'],
        ]);

        if ($sale->status !== 'completed') {
            return back()->withErrors('Solo ventas completadas pueden ajustarse administrativamente.')->withInput();
        }

        if ($validated['action_type'] === 'void_invoice' && $sale->document_type !== 'factura') {
            return back()->withErrors('Solo las facturas pueden anularse con esta accion.')->withInput();
        }

        $refundCash = $request->boolean('refund_cash');
        $refundAmount = $refundCash ? round((float) ($validated['refund_amount'] ?? 0), 2) : 0.0;
        $returnProducts = $request->boolean('return_products');
        $notes = trim((string) ($validated['notes'] ?? ''));

        if (! $refundCash && ! $returnProducts) {
            return back()->withErrors('Debes registrar devolucion de efectivo, devolucion de producto o ambas.')->withInput();
        }

        if (! $this->isAdminRole() && ! $this->validateAdminPassword($validated['admin_password'] ?? null, $companyId)) {
            return back()->withErrors('Se requiere clave de administrador valida para procesar la cancelacion/anulacion.')->withInput();
        }

        if ($refundCash && ($refundAmount <= 0 || $refundAmount > (float) $sale->total)) {
            return back()->withErrors('El monto de devolucion de efectivo debe ser mayor a 0 y menor o igual al total de la venta.')->withInput();
        }

        $rawItems = collect($validated['items'] ?? [])
            ->map(function ($row) {
                return [
                    'sale_item_id' => (int) ($row['sale_item_id'] ?? 0),
                    'quantity' => (int) ($row['quantity'] ?? 0),
                    'condition' => (string) ($row['condition'] ?? ''),
                ];
            })
            ->filter(fn ($row) => $row['sale_item_id'] > 0 && $row['quantity'] > 0)
            ->values();

        if ($returnProducts && $rawItems->isEmpty()) {
            return back()->withErrors('Debes indicar al menos un producto para devolver.')->withInput();
        }

        $saleItemsById = $sale->items->keyBy('id');
        foreach ($rawItems as $row) {
            $saleItem = $saleItemsById->get($row['sale_item_id']);
            if (! $saleItem) {
                return back()->withErrors('Uno o mas productos no pertenecen a la venta seleccionada.')->withInput();
            }

            if ((int) $row['quantity'] > (int) $saleItem->quantity) {
                return back()->withErrors('La cantidad devuelta no puede ser mayor a la cantidad vendida.')->withInput();
            }

            if (! in_array($row['condition'], ['unopened', 'damaged', 'expired'], true)) {
                return back()->withErrors('Debes indicar el estado de cada producto devuelto.')->withInput();
            }
        }

        DB::transaction(function () use ($validated, $refundCash, $refundAmount, $returnProducts, $notes, $rawItems, $sale, $companyId, $warehouseId, $saleItemsById) {
            $adjustmentId = DB::table('sale_adjustments')->insertGetId([
                'sale_id' => $sale->id,
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'processed_by_user_id' => auth()->id(),
                'action_type' => $validated['action_type'],
                'refund_cash' => $refundCash,
                'refund_amount' => $refundAmount,
                'return_products' => $returnProducts,
                'notes' => $notes !== '' ? $notes : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($refundCash) {
                $activePosSessionId = DB::table('pos_sessions')
                    ->where('company_id', $companyId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('user_id', auth()->id())
                    ->whereNull('closed_at')
                    ->latest('id')
                    ->value('id');

                DB::table('pos_cash_movements')->insert([
                    'pos_session_id' => $activePosSessionId ?: null,
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'user_id' => auth()->id(),
                    'type' => 'out',
                    'amount' => $refundAmount,
                    'reason' => 'Devolucion de efectivo por ajuste de venta #' . $sale->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($rawItems as $row) {
                $saleItem = $saleItemsById->get($row['sale_item_id']);
                $restocked = $row['condition'] === 'unopened';

                DB::table('sale_adjustment_items')->insert([
                    'sale_adjustment_id' => $adjustmentId,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => (int) $row['quantity'],
                    'product_condition' => $row['condition'],
                    'restocked' => $restocked,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($restocked) {
                    InventoryMovement::create([
                        'company_id' => $companyId,
                        'warehouse_id' => $warehouseId,
                        'product_id' => $saleItem->product_id,
                        'type' => 'in',
                        'quantity' => (int) $row['quantity'],
                        'reference_type' => 'sale_adjustment_restock',
                        'reference_id' => $adjustmentId,
                        'user_id' => auth()->id(),
                        'reason' => 'Devolucion sin abrir de venta #' . $sale->id,
                    ]);
                } else {
                    NonSellableProduct::create([
                        'company_id' => $companyId,
                        'warehouse_id' => $warehouseId,
                        'product_id' => $saleItem->product_id,
                        'quantity' => (int) $row['quantity'],
                        'condition' => $row['condition'],
                        'source_type' => 'sale_adjustment',
                        'source_id' => $adjustmentId,
                        'reason' => 'Producto devuelto no vendible de venta #' . $sale->id,
                        'reported_by_user_id' => auth()->id(),
                    ]);
                }
            }

            $newStatus = $validated['action_type'] === 'cancel_sale' ? 'cancelled' : 'annulled';
            $sale->update(['status' => $newStatus]);
        });

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Ajuste administrativo aplicado correctamente. El movimiento de caja se registro automaticamente cuando hubo devolucion de efectivo.');
    }

    public function cancel(Sale $sale)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $this->ensureSaleAccess($sale, $companyId, $warehouseId);

        if (! $this->isAdminRole()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        try {
            $sale->cancel();
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }

        return redirect()
            ->route('sales.index')
            ->with('success', 'Venta cancelada correctamente');
    }

    private function resolveTipoDte(string $documentType): ?string
    {
        return $documentType === 'factura' ? '01' : null;
    }
}
