<?php

namespace Stockia\PTVPos\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryMovement;
use App\Models\CompanyUser;
use App\Models\Company;

class PTVPosController extends Controller
{
    private function getDocumentTemplate(int $companyId, string $documentType): object
    {
        $template = DB::table('pos_document_templates')
            ->where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->first();

        if ($template) {
            return $template;
        }

        $company = Company::find($companyId);

        return (object) [
            'document_type' => $documentType,
            'template_name' => strtoupper($documentType),
            'header_text' => $company?->legal_name ?: $company?->name,
            'footer_text' => $company?->ticket_footer ?: 'Gracias por su compra.',
            'terms_text' => null,
        ];
    }

    private function findSaleInContext(int $saleId, int $companyId, int $warehouseId): Sale
    {
        return Sale::where('id', $saleId)
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->firstOrFail();
    }

    private function getPendingPrintSale(int $companyId, int $warehouseId, int $userId): ?Sale
    {
        return Sale::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('user_id', $userId)
            ->where('status', 'pending_print')
            ->latest('id')
            ->first();
    }

    private function getActiveSession(int $companyId, int $warehouseId, int $userId): ?object
    {
        return DB::table('pos_sessions')
            ->leftJoin('pos_registers', 'pos_registers.id', '=', 'pos_sessions.register_id')
            ->where('pos_sessions.company_id', $companyId)
            ->where('pos_sessions.warehouse_id', $warehouseId)
            ->where('pos_sessions.user_id', $userId)
            ->whereNull('pos_sessions.closed_at')
            ->latest('pos_sessions.id')
            ->first([
                'pos_sessions.*',
                'pos_registers.name as register_name',
                'pos_registers.code as register_code',
            ]);
    }

    private function calculateSessionCashSnapshot(object $session, int $companyId, int $warehouseId, int $userId): array
    {
        $openedAt = $session->opened_at;
        $closedAt = $session->closed_at ?: now();

        $salesTotal = (float) Sale::query()
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $openedAt)
            ->where('created_at', '<=', $closedAt)
            ->sum('total');

        $manualIn = (float) DB::table('pos_cash_movements')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('user_id', $userId)
            ->where('pos_session_id', $session->id)
            ->where('type', 'in')
            ->sum('amount');

        $manualOut = (float) DB::table('pos_cash_movements')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('user_id', $userId)
            ->where('pos_session_id', $session->id)
            ->where('type', 'out')
            ->sum('amount');

        $expectedCash = round((float) $session->opening_cash + $salesTotal + $manualIn - $manualOut, 2);

        return [
            'sales_total' => round($salesTotal, 2),
            'manual_in' => round($manualIn, 2),
            'manual_out' => round($manualOut, 2),
            'expected_cash' => $expectedCash,
        ];
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

    private function hasOpenSession(int $companyId, int $warehouseId, int $userId): bool
    {
        return $this->getActiveSession($companyId, $warehouseId, $userId) !== null;
    }

    private function getOpenSessionsForWarehouse(int $companyId, int $warehouseId)
    {
        return DB::table('pos_sessions')
            ->leftJoin('pos_registers', 'pos_registers.id', '=', 'pos_sessions.register_id')
            ->leftJoin('users', 'users.id', '=', 'pos_sessions.user_id')
            ->where('pos_sessions.company_id', $companyId)
            ->where('pos_sessions.warehouse_id', $warehouseId)
            ->whereNull('pos_sessions.closed_at')
            ->orderByDesc('pos_sessions.id')
            ->get([
                'pos_sessions.*',
                'pos_registers.name as register_name',
                'pos_registers.code as register_code',
                'users.name as user_name',
            ]);
    }

    public function index()
    {
        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $userId = (int) auth()->id();
        $todayStart = now()->startOfDay();

        $salesBase = Sale::query()
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $todayStart);

        $salesCount = (clone $salesBase)->count();
        $grossTotal = (float) ((clone $salesBase)->sum('total') ?? 0);
        $cashTotal = (float) ((clone $salesBase)->sum('cash_received') ?? 0);
        $avgTicket = $salesCount > 0 ? round($grossTotal / $salesCount, 2) : 0.0;

        $itemsSold = (int) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.warehouse_id', $warehouseId)
            ->where('sales.user_id', $userId)
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', $todayStart)
            ->sum('sale_items.quantity');

        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.warehouse_id', $warehouseId)
            ->where('sales.user_id', $userId)
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', $todayStart)
            ->groupBy('sale_items.product_id', 'products.name')
            ->orderByDesc(DB::raw('SUM(sale_items.quantity)'))
            ->limit(5)
            ->get([
                'sale_items.product_id',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as qty'),
                DB::raw('SUM(sale_items.subtotal) as subtotal'),
            ]);

        $latestSales = (clone $salesBase)
            ->latest('id')
            ->limit(5)
            ->get(['id', 'total', 'cash_received', 'change_amount', 'created_at', 'document_type']);

        $activeSession = $this->getActiveSession($companyId, $warehouseId, $userId);

        return view('ptvpos::index', compact(
            'salesCount',
            'grossTotal',
            'cashTotal',
            'avgTicket',
            'itemsSold',
            'topProducts',
            'latestSales',
            'activeSession',
        ));
    }

    public function pos()
    {
        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $userId = (int) auth()->id();
        $activeSession = $this->getActiveSession($companyId, $warehouseId, $userId);
        if (! $activeSession) {
            return redirect()->route('ptvpos.open')
                ->with('error', 'Debes abrir caja antes de usar el Punto de Venta.');
        }

        $pendingPrintSale = $this->getPendingPrintSale($companyId, $warehouseId, $userId);
        if ($pendingPrintSale) {
            return redirect()->route('ptvpos.sales.print', $pendingPrintSale->id)
                ->with('error', 'Tienes una venta pendiente por imprimir. Debes finalizarla primero.');
        }

        $snapshot = $this->calculateSessionCashSnapshot($activeSession, $companyId, $warehouseId, $userId);
        $recentCashMovements = DB::table('pos_cash_movements')
            ->where('pos_session_id', $activeSession->id)
            ->latest('id')
            ->limit(8)
            ->get();

        return view('ptvpos::pos', compact('activeSession', 'snapshot', 'recentCashMovements'));
    }

    public function cashMovements(Request $request)
    {
        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $userId = (int) auth()->id();
        $role = function_exists('currentRole') ? currentRole() : null;
        $isAdminRole = in_array($role, ['Admin', 'SuperAdmin'], true);
        $activeSession = $this->getActiveSession($companyId, $warehouseId, $userId);

        $movementsQuery = DB::table('pos_cash_movements')
            ->leftJoin('pos_sessions', 'pos_sessions.id', '=', 'pos_cash_movements.pos_session_id')
            ->leftJoin('pos_registers', 'pos_registers.id', '=', 'pos_sessions.register_id')
            ->leftJoin('users', 'users.id', '=', 'pos_cash_movements.user_id')
            ->where('pos_cash_movements.company_id', $companyId)
            ->where('pos_cash_movements.warehouse_id', $warehouseId);

        if (! $isAdminRole) {
            $movementsQuery->where('pos_cash_movements.user_id', $userId);
        }

        $filterType = (string) $request->query('type', '');
        if (in_array($filterType, ['in', 'out'], true)) {
            $movementsQuery->where('pos_cash_movements.type', $filterType);
        }

        if ($activeSession) {
            $showAll = $isAdminRole && (string) $request->query('scope') === 'all';
            if (! $showAll) {
                $movementsQuery->where('pos_cash_movements.pos_session_id', $activeSession->id);
            }
            $snapshot = $this->calculateSessionCashSnapshot($activeSession, $companyId, $warehouseId, $userId);
        } else {
            $snapshot = null;
            if (! $isAdminRole) {
                $movementsQuery->limit(20);
            }
        }

        $movements = $movementsQuery
            ->orderByDesc('pos_cash_movements.id')
            ->limit(100)
            ->get([
                'pos_cash_movements.*',
                'pos_registers.name as register_name',
                'pos_registers.code as register_code',
                'users.name as user_name',
            ]);

        return view('ptvpos::cash-movements', compact('activeSession', 'snapshot', 'movements', 'filterType', 'isAdminRole'));
    }

    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Barcode invalido.'], 422);
        }

        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $userId = (int) auth()->id();
        $code = trim((string) $request->barcode);

        if (! $this->hasOpenSession($companyId, $warehouseId, $userId)) {
            return response()->json(['message' => 'Debes abrir caja antes de escanear.'], 409);
        }

        $product = Product::where('company_id', $companyId)
            ->where(function ($query) use ($code) {
                $query->where('barcode', $code)
                    ->orWhere('sku', $code);
            })
            ->whereHas('warehouses', function ($query) use ($warehouseId) {
                $query->where('warehouses.id', $warehouseId);
            })
            ->with(['warehouses' => function ($query) use ($warehouseId) {
                $query->where('warehouses.id', $warehouseId);
            }])
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Producto no encontrado en el almacen actual.'], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
        ]);
    }

    public function adminAuth(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $companyId = (int) session('current_company_id');

        if ($this->validateAdminPassword($request->password, $companyId)) {
            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => false, 'message' => 'Clave de administrador invalida.'], 403);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'cash_received' => ['required', 'numeric', 'min:0'],
            'document_type' => ['required', 'in:ticket,factura'],
            'admin_password' => ['nullable', 'string'],
            'force_stock_adjustment' => ['nullable', 'boolean'],
            'stock_adjustment_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $userId = (int) auth()->id();

        if (! $this->hasOpenSession($companyId, $warehouseId, $userId)) {
            return redirect()->route('ptvpos.open')
                ->with('error', 'Debes abrir caja antes de registrar ventas.');
        }

        $pendingPrintSale = $this->getPendingPrintSale($companyId, $warehouseId, $userId);
        if ($pendingPrintSale) {
            return redirect()->route('ptvpos.sales.print', $pendingPrintSale->id)
                ->with('error', 'Tienes una venta pendiente por imprimir. Debes finalizarla antes de registrar otra.');
        }

        $items = collect($data['items'])
            ->map(function ($item) {
                return [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'price' => (float) $item['price'],
                ];
            })
            ->groupBy('product_id')
            ->map(function ($rows) {
                $lastRow = $rows->last();

                return [
                    'product_id' => (int) $lastRow['product_id'],
                    'quantity' => (int) $rows->sum('quantity'),
                    'price' => (float) $lastRow['price'],
                ];
            })
            ->values();

        $productIds = $items->pluck('product_id')->unique()->values();
        $products = Product::where('company_id', $companyId)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');
        $stocks = DB::table('product_warehouse')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->pluck('stock', 'product_id');

        $subtotal = 0.0;
        $shortages = [];
        foreach ($items as $item) {
            if (! $products->has($item['product_id'])) {
                return back()->withErrors('Producto invalido.')->withInput();
            }

            if (! $stocks->has($item['product_id'])) {
                return back()->withErrors('Producto no disponible en el almacen actual.')->withInput();
            }

            $availableStock = (int) $stocks->get($item['product_id']);
            if ($item['quantity'] > $availableStock) {
                $shortages[] = [
                    'product_id' => $item['product_id'],
                    'missing_qty' => $item['quantity'] - $availableStock,
                ];
            }

            $subtotal += $item['price'] * $item['quantity'];
        }

        $taxTotal = round($subtotal * 0.13, 2);
        $total = round($subtotal + $taxTotal, 2);
        $cashReceived = round((float) $data['cash_received'], 2);

        if ($cashReceived < $total) {
            return back()->withErrors('El efectivo recibido no cubre el total.')->withInput();
        }

        $forceStockAdjustment = (bool) ($data['force_stock_adjustment'] ?? false);
        $stockAdjustmentReason = trim((string) ($data['stock_adjustment_reason'] ?? ''));

        if (! empty($shortages)) {
            if (! $forceStockAdjustment) {
                return back()->withErrors('Stock insuficiente en sistema. Solicita autorizacion de administrador para ajuste temporal.')->withInput();
            }

            if ($stockAdjustmentReason === '') {
                return back()->withErrors('Debes indicar un motivo para el ajuste temporal de stock.')->withInput();
            }

            if (! $this->validateAdminPassword($data['admin_password'] ?? null, $companyId)) {
                return back()->withErrors('Se requiere clave de administrador valida para autorizar ajuste de stock.')->withInput();
            }
        }

        $changeAmount = round($cashReceived - $total, 2);

        try {
            DB::transaction(function () use ($companyId, $warehouseId, $items, $products, $subtotal, $taxTotal, $total, $cashReceived, $changeAmount, $data, $shortages, $stockAdjustmentReason) {
                if (! empty($shortages)) {
                    foreach ($shortages as $shortage) {
                        InventoryMovement::create([
                            'company_id' => $companyId,
                            'warehouse_id' => $warehouseId,
                            'product_id' => (int) $shortage['product_id'],
                            'type' => 'in',
                            'quantity' => (int) $shortage['missing_qty'],
                            'reference_type' => 'pos_stock_adjustment',
                            'reference_id' => null,
                            'reason' => $stockAdjustmentReason,
                            'user_id' => auth()->id(),
                        ]);
                    }
                }

                $sale = Sale::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'user_id' => auth()->id(),
                    'status' => 'pending_print',
                    'subtotal' => $subtotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                    'payment_method' => 'cash',
                    'cash_received' => $cashReceived,
                    'change_amount' => $changeAmount,
                    'document_type' => $data['document_type'],
                ]);

                foreach ($items as $item) {
                    $product = $products->get($item['product_id']);
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['price'] * $item['quantity'],
                    ]);

                    InventoryMovement::create([
                        'company_id' => $companyId,
                        'warehouse_id' => $warehouseId,
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => $item['quantity'],
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'user_id' => auth()->id(),
                    ]);
                }
            });
        } catch (Throwable $e) {
            return back()->withErrors('No fue posible completar la venta: ' . $e->getMessage())->withInput();
        }

        $pendingPrintSale = $this->getPendingPrintSale($companyId, $warehouseId, $userId);
        if (! $pendingPrintSale) {
            return redirect()->route('ptvpos.pos')->with('error', 'No se encontro la venta para impresion.');
        }

        return redirect()->route('ptvpos.sales.print', $pendingPrintSale->id)
            ->with('success', 'Venta registrada. Imprime el comprobante para finalizar.');
    }

    public function printSale(int $sale)
    {
        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $sale = $this->findSaleInContext($sale, $companyId, $warehouseId);
        $sale->load(['items.product', 'company', 'warehouse', 'user']);
        $template = $this->getDocumentTemplate($companyId, (string) $sale->document_type);

        return view('ptvpos::print-sale', compact('sale', 'template'));
    }

    public function salePdf(int $sale)
    {
        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $sale = $this->findSaleInContext($sale, $companyId, $warehouseId);
        $sale->load(['items.product', 'company', 'warehouse', 'user']);
        $template = $this->getDocumentTemplate($companyId, (string) $sale->document_type);

        if (! app()->bound('dompdf.wrapper')) {
            return redirect()->route('ptvpos.sales.print', $sale->id)
                ->with('error', 'PDF no disponible: instala barryvdh/laravel-dompdf en el CORE.');
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('ptvpos::sale-pdf', compact('sale', 'template'));

        return $pdf->stream('comprobante-venta-' . $sale->id . '.pdf');
    }

    public function completePrintedSale(Request $request, int $sale): RedirectResponse
    {
        $validated = $request->validate([
            'printed_ack' => ['required', 'accepted'],
        ]);

        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $sale = $this->findSaleInContext($sale, $companyId, $warehouseId);

        if ($sale->status !== 'pending_print') {
            return redirect()->route('ptvpos.pos')->with('error', 'La venta ya fue finalizada.');
        }

        $sale->update(['status' => 'completed']);

        return redirect()->route('ptvpos.pos')->with('success', 'Venta finalizada despues de impresion.');
    }

    public function open()
    {
        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $userId = (int) auth()->id();

        $registers = DB::table('pos_registers')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $activeSession = DB::table('pos_sessions')
            ->leftJoin('pos_registers', 'pos_registers.id', '=', 'pos_sessions.register_id')
            ->where('pos_sessions.company_id', $companyId)
            ->where('pos_sessions.warehouse_id', $warehouseId)
            ->where('pos_sessions.user_id', $userId)
            ->whereNull('pos_sessions.closed_at')
            ->latest('pos_sessions.id')
            ->first([
                'pos_sessions.*',
                'pos_registers.name as register_name',
                'pos_registers.code as register_code',
            ]);

        return view('ptvpos::open', compact('registers', 'activeSession'));
    }

    public function storeOpen(Request $request): RedirectResponse
    {
        $request->validate([
            'register_id' => ['required', 'integer'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $userId = (int) auth()->id();
        $registerId = (int) $request->register_id;

        $register = DB::table('pos_registers')
            ->where('id', $registerId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (! $register) {
            return back()->withErrors('La caja seleccionada no es valida o esta inactiva.');
        }

        if ($this->hasOpenSession($companyId, $warehouseId, $userId)) {
            return back()->withErrors('Ya tienes una caja abierta. Debes cerrarla antes de abrir otra.');
        }

        $registerBusy = DB::table('pos_sessions')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('register_id', $registerId)
            ->whereNull('closed_at')
            ->exists();

        if ($registerBusy) {
            return back()->withErrors('La caja seleccionada ya esta abierta por otro usuario.');
        }

        DB::table('pos_sessions')->insert([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'user_id' => $userId,
            'register_id' => $registerId,
            'opening_cash' => $request->opening_cash,
            'opened_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('ptvpos.index')->with('success', 'Caja abierta.');
    }

    public function storeCashMovement(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => ['required', 'in:in,out'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $userId = (int) auth()->id();
        $activeSession = $this->getActiveSession($companyId, $warehouseId, $userId);

        if (! $activeSession) {
            return redirect()->route('ptvpos.open')
                ->with('error', 'Debes abrir caja antes de registrar movimientos manuales.');
        }

        DB::table('pos_cash_movements')->insert([
            'pos_session_id' => $activeSession->id,
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'user_id' => $userId,
            'type' => $request->type,
            'amount' => round((float) $request->amount, 2),
            'reason' => trim((string) $request->reason),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('ptvpos.cash-movements.index')->with('success', 'Movimiento de caja registrado.');
    }

    public function close()
    {
        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $userId = (int) auth()->id();
        $role = function_exists('currentRole') ? currentRole() : null;
        $isAdminRole = in_array($role, ['Admin', 'SuperAdmin'], true);

        $activeSession = $this->getActiveSession($companyId, $warehouseId, $userId);
        $openSessions = collect();
        $selectedSessionId = null;
        $snapshot = null;
        $recentCashMovements = collect();

        if ($isAdminRole) {
            $openSessions = $this->getOpenSessionsForWarehouse($companyId, $warehouseId);
            $requestedSessionId = (int) request()->query('target_session_id', old('target_session_id', 0));

            if ($requestedSessionId > 0) {
                $activeSession = $openSessions->firstWhere('id', $requestedSessionId) ?: $activeSession;
            } elseif (! $activeSession && $openSessions->isNotEmpty()) {
                $activeSession = $openSessions->first();
            }
        }

        if ($activeSession) {
            $selectedSessionId = (int) $activeSession->id;
            $snapshot = $this->calculateSessionCashSnapshot($activeSession, $companyId, $warehouseId, (int) $activeSession->user_id);
            $recentCashMovements = DB::table('pos_cash_movements')
                ->leftJoin('pos_sessions', 'pos_sessions.id', '=', 'pos_cash_movements.pos_session_id')
                ->leftJoin('pos_registers', 'pos_registers.id', '=', 'pos_sessions.register_id')
                ->leftJoin('users', 'users.id', '=', 'pos_cash_movements.user_id')
                ->where('pos_cash_movements.pos_session_id', $activeSession->id)
                ->orderByDesc('pos_cash_movements.id')
                ->limit(10)
                ->get([
                    'pos_cash_movements.*',
                    'pos_registers.name as register_name',
                    'pos_registers.code as register_code',
                    'users.name as user_name',
                ]);
        } elseif ($isAdminRole) {
            $recentCashMovements = DB::table('pos_cash_movements')
                ->leftJoin('pos_sessions', 'pos_sessions.id', '=', 'pos_cash_movements.pos_session_id')
                ->leftJoin('pos_registers', 'pos_registers.id', '=', 'pos_sessions.register_id')
                ->leftJoin('users', 'users.id', '=', 'pos_cash_movements.user_id')
                ->where('pos_cash_movements.company_id', $companyId)
                ->where('pos_cash_movements.warehouse_id', $warehouseId)
                ->orderByDesc('pos_cash_movements.id')
                ->limit(20)
                ->get([
                    'pos_cash_movements.*',
                    'pos_registers.name as register_name',
                    'pos_registers.code as register_code',
                    'users.name as user_name',
                ]);
        }

        return view('ptvpos::close', compact(
            'activeSession',
            'snapshot',
            'recentCashMovements',
            'isAdminRole',
            'openSessions',
            'selectedSessionId',
        ));
    }

    public function templates()
    {
        $companyId = (int) session('current_company_id');
        $ticketTemplate = $this->getDocumentTemplate($companyId, 'ticket');
        $invoiceTemplate = $this->getDocumentTemplate($companyId, 'factura');

        return view('ptvpos::admin.templates', compact('ticketTemplate', 'invoiceTemplate'));
    }

    public function saveTemplates(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ticket.template_name' => ['nullable', 'string', 'max:120'],
            'ticket.header_text' => ['nullable', 'string', 'max:3000'],
            'ticket.footer_text' => ['nullable', 'string', 'max:3000'],
            'ticket.terms_text' => ['nullable', 'string', 'max:3000'],
            'factura.template_name' => ['nullable', 'string', 'max:120'],
            'factura.header_text' => ['nullable', 'string', 'max:3000'],
            'factura.footer_text' => ['nullable', 'string', 'max:3000'],
            'factura.terms_text' => ['nullable', 'string', 'max:3000'],
        ]);

        $companyId = (int) session('current_company_id');

        foreach (['ticket', 'factura'] as $documentType) {
            $payload = $validated[$documentType] ?? [];

            DB::table('pos_document_templates')->updateOrInsert(
                [
                    'company_id' => $companyId,
                    'document_type' => $documentType,
                ],
                [
                    'template_name' => $payload['template_name'] ?? null,
                    'header_text' => $payload['header_text'] ?? null,
                    'footer_text' => $payload['footer_text'] ?? null,
                    'terms_text' => $payload['terms_text'] ?? null,
                    'updated_at' => now(),
                ]
            );
        }

        return redirect()->route('ptvpos.admin.templates.index')
            ->with('success', 'Plantillas de comprobantes actualizadas.');
    }

    public function storeClose(Request $request): RedirectResponse
    {
        $request->validate([
            'closing_cash' => ['required', 'numeric', 'min:0'],
            'closing_note' => ['nullable', 'string', 'max:255'],
            'target_session_id' => ['nullable', 'integer'],
        ]);

        $companyId = (int) session('current_company_id');
        $warehouseId = (int) session('current_warehouse_id');
        $userId = (int) auth()->id();
        $role = function_exists('currentRole') ? currentRole() : null;
        $isAdminRole = in_array($role, ['Admin', 'SuperAdmin'], true);
        $requestedSessionId = (int) ($request->input('target_session_id') ?? 0);

        $activeSession = null;

        if ($isAdminRole && $requestedSessionId > 0) {
            $activeSession = DB::table('pos_sessions')
                ->leftJoin('pos_registers', 'pos_registers.id', '=', 'pos_sessions.register_id')
                ->leftJoin('users', 'users.id', '=', 'pos_sessions.user_id')
                ->where('pos_sessions.id', $requestedSessionId)
                ->where('pos_sessions.company_id', $companyId)
                ->where('pos_sessions.warehouse_id', $warehouseId)
                ->whereNull('pos_sessions.closed_at')
                ->first([
                    'pos_sessions.*',
                    'pos_registers.name as register_name',
                    'pos_registers.code as register_code',
                    'users.name as user_name',
                ]);
        } else {
            $activeSession = $this->getActiveSession($companyId, $warehouseId, $userId);
        }

        if (! $activeSession) {
            return back()->withErrors('No se encontro una caja abierta para cerrar con los datos indicados.')->withInput();
        }

        $snapshot = $this->calculateSessionCashSnapshot($activeSession, $companyId, $warehouseId, (int) $activeSession->user_id);
        $closingCash = round((float) $request->closing_cash, 2);
        $difference = round($closingCash - (float) $snapshot['expected_cash'], 2);
        $closingNote = trim((string) ($request->closing_note ?? ''));

        if ($difference !== 0.0 && $closingNote === '') {
            return back()->withErrors('Debes ingresar una observacion cuando hay diferencia en caja.')->withInput();
        }

        $updated = DB::table('pos_sessions')
            ->where('id', $activeSession->id)
            ->whereNull('closed_at')
            ->update([
                'closing_cash' => $closingCash,
                'expected_cash' => $snapshot['expected_cash'],
                'cash_difference' => $difference,
                'closing_note' => $closingNote !== '' ? $closingNote : null,
                'closed_at' => now(),
                'updated_at' => now(),
            ]);

        if (! $updated) {
            return back()->withErrors('La caja ya fue cerrada por otro proceso.')->withInput();
        }

        $registerLabel = $activeSession->register_name ?? ('#' . $activeSession->register_id);
        $userLabel = $activeSession->user_name ?? ('#' . $activeSession->user_id);

        return redirect()->route('ptvpos.index')
            ->with('success', 'Caja cerrada (' . $registerLabel . ', usuario ' . $userLabel . '). Esperado: $' . number_format($snapshot['expected_cash'], 2) . ', contado: $' . number_format($closingCash, 2) . ', diferencia: $' . number_format($difference, 2) . '.');
    }
}
