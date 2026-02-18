<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\NonSellableProduct;
use App\Models\CompanyUser;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    private const IVA_RATE = 0.13;

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

        return view('sales.create', compact('products'));
    }

    public function show($id)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $sale = $this->findSaleInContext((int) $id, $companyId, $warehouseId);
        $this->ensureSaleAccess($sale, $companyId, $warehouseId);
        $sale->load(['items.product', 'company', 'warehouse', 'user']);

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
        ]);

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
        $subtotal = 0.0;

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

            $lineSubtotal = round(((float) $product->price) * $quantity, 2);
            $subtotal += $lineSubtotal;

            $lineItems[] = [
                'product' => $product,
                'quantity' => $quantity,
                'price' => (float) $product->price,
                'subtotal' => $lineSubtotal,
            ];
        }

        $taxTotal = round($subtotal * self::IVA_RATE, 2);
        $grandTotal = round($subtotal + $taxTotal, 2);
        $cashReceived = round((float) $validated['cash_received'], 2);

        if ($cashReceived < $grandTotal) {
            return back()->withErrors('El efectivo recibido no cubre el total de la venta.');
        }

        $changeAmount = round($cashReceived - $grandTotal, 2);

        DB::transaction(function () use (
            $lineItems,
            $warehouseId,
            $companyId,
            $subtotal,
            $taxTotal,
            $grandTotal,
            $cashReceived,
            $changeAmount,
            $validated
        ) {
            $sale = Sale::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'user_id' => auth()->id(),
                'status' => 'completed',
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $grandTotal,
                'payment_method' => 'cash',
                'cash_received' => $cashReceived,
                'change_amount' => $changeAmount,
                'document_type' => $validated['document_type'],
            ]);

            foreach ($lineItems as $lineItem) {
                $product = $lineItem['product'];
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $lineItem['quantity'],
                    'price' => $lineItem['price'],
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
        });

        return redirect()->route('sales.index')
            ->with('success', 'Venta registrada correctamente');
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

        return $this->streamSalePdf(
            'sales.pdf-invoice',
            $sale,
            'factura-venta-' . $sale->id . '.pdf',
            route('sales.invoice', $sale)
        );
    }

    private function streamSalePdf(string $view, Sale $sale, string $filename, string $fallbackRoute)
    {
        if (! app()->bound('dompdf.wrapper')) {
            return redirect($fallbackRoute)
                ->withErrors('PDF no disponible: instala barryvdh/laravel-dompdf en el CORE.');
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView($view, compact('sale'));

        return $pdf->stream($filename);
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
}
