<?php

namespace Stockia\SalesQuotes\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CustomerOrderController extends Controller
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

    private function isAdminRole(): bool
    {
        $role = function_exists('currentRole') ? currentRole() : null;

        return in_array($role, ['Admin', 'SuperAdmin'], true);
    }

    private function findOrderInContext(int $orderId, int $companyId, int $warehouseId): object
    {
        $query = DB::table('customer_orders')
            ->leftJoin('users', 'users.id', '=', 'customer_orders.user_id')
            ->where('customer_orders.id', $orderId)
            ->where('customer_orders.company_id', $companyId)
            ->where('customer_orders.warehouse_id', $warehouseId);

        if (! $this->isAdminRole()) {
            $query->where('customer_orders.user_id', auth()->id());
        }

        $order = $query->first([
            'customer_orders.*',
            'users.name as user_name',
        ]);

        if (! $order) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $order;
    }

    public function index()
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $ordersQuery = DB::table('customer_orders')
            ->leftJoin('users', 'users.id', '=', 'customer_orders.user_id')
            ->where('customer_orders.company_id', $companyId)
            ->where('customer_orders.warehouse_id', $warehouseId);

        if (! $this->isAdminRole()) {
            $ordersQuery->where('customer_orders.user_id', auth()->id());
        }

        $orders = $ordersQuery
            ->orderByDesc('customer_orders.id')
            ->paginate(20, [
                'customer_orders.*',
                'users.name as user_name',
            ]);

        return view('salesquotes::orders.index', compact('orders'));
    }

    public function create()
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $products = Product::query()
            ->where('company_id', $companyId)
            ->whereHas('warehouses', function ($query) use ($warehouseId) {
                $query->where('warehouses.id', $warehouseId);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'price']);

        $stocks = DB::table('product_warehouse')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->pluck('stock', 'product_id');

        $products = $products->map(function ($product) use ($stocks) {
            $product->stock = (int) ($stocks[$product->id] ?? 0);
            return $product;
        });

        return view('salesquotes::orders.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'fecha_entrega_estimada' => ['nullable', 'date'],
            'anticipo' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $items = collect($validated['items'])
            ->filter(function ($item) {
                $productId = trim((string) ($item['product_id'] ?? ''));
                $quantity = trim((string) ($item['quantity'] ?? ''));
                $price = trim((string) ($item['price'] ?? ''));
                $discount = trim((string) ($item['discount_percent'] ?? ''));

                return $productId !== '' || $quantity !== '' || $price !== '' || $discount !== '';
            })
            ->map(function ($item) {
                return [
                    'product_id' => (int) ($item['product_id'] ?? 0),
                    'quantity' => (int) ($item['quantity'] ?? 0),
                    'price' => round((float) ($item['price'] ?? 0), 2),
                    'discount_percent' => round((float) ($item['discount_percent'] ?? 0), 2),
                ];
            })
            ->values();

        if ($items->isEmpty()) {
            return back()->withErrors('Debes agregar al menos un producto válido.')->withInput();
        }

        foreach ($items as $item) {
            if ($item['product_id'] <= 0 || $item['quantity'] <= 0) {
                return back()->withErrors('Cada línea usada debe tener producto y cantidad válidos.')->withInput();
            }
        }

        $productIds = $items->pluck('product_id')->unique()->values();

        $products = Product::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $productIds)
            ->whereHas('warehouses', function ($query) use ($warehouseId) {
                $query->where('warehouses.id', $warehouseId);
            })
            ->get(['id', 'price'])
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            return back()->withErrors('Uno o más productos no pertenecen al almacén actual.')->withInput();
        }

        $stocks = DB::table('product_warehouse')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_id', $productIds)
            ->pluck('stock', 'product_id');

        $normalizedItems = [];
        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            $currentPrice = round((float) $product->price, 2);
            $providedPrice = round((float) $item['price'], 2);
            $currentStock = (int) ($stocks[$item['product_id']] ?? 0);
            $qty = (int) $item['quantity'];
            $discountPercent = round((float) ($item['discount_percent'] ?? 0), 2);

            if ($qty > $currentStock) {
                return back()->withErrors('Cantidad solicitada supera stock disponible para uno o más productos.')->withInput();
            }

            if ($providedPrice !== $currentPrice) {
                return back()->withErrors('El precio cambió en uno o más productos. Actualiza el apartado con el precio vigente.')->withInput();
            }

            $baseSubtotal = round($qty * $currentPrice, 2);
            $discountAmount = round($baseSubtotal * ($discountPercent / 100), 2);
            $lineSubtotal = round($baseSubtotal - $discountAmount, 2);

            $normalizedItems[] = [
                'product_id' => $item['product_id'],
                'quantity' => $qty,
                'price' => $currentPrice,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'subtotal' => $lineSubtotal,
            ];
        }

        $subtotal = round((float) collect($normalizedItems)->sum('subtotal'), 2);
        $taxTotal = round($subtotal * self::IVA_RATE, 2);
        $total = round($subtotal + $taxTotal, 2);

        $anticipo = round((float) ($validated['anticipo'] ?? 0), 2);
        if ($anticipo > $total) {
            return back()->withErrors('El anticipo no puede ser mayor al total del apartado.')->withInput();
        }
        $saldoPendiente = round($total - $anticipo, 2);

        $orderId = DB::transaction(function () use (
            $validated,
            $normalizedItems,
            $companyId,
            $warehouseId,
            $subtotal,
            $taxTotal,
            $total,
            $anticipo,
            $saldoPendiente
        ) {
            $orderId = DB::table('customer_orders')->insertGetId([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'user_id' => auth()->id(),
                'order_number' => null,
                'status' => 'pendiente',
                'customer_name' => trim((string) $validated['customer_name']),
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'fecha_entrega_estimada' => $validated['fecha_entrega_estimada'] ?? null,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'anticipo' => $anticipo,
                'saldo_pendiente' => $saldoPendiente,
                'notes' => $validated['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $orderNumber = 'APT-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);
            DB::table('customer_orders')->where('id', $orderId)->update(['order_number' => $orderNumber]);

            foreach ($normalizedItems as $item) {
                DB::table('customer_order_items')->insert([
                    'customer_order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'discount_percent' => $item['discount_percent'],
                    'discount_amount' => $item['discount_amount'],
                    'subtotal' => $item['subtotal'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // La mercadería queda reservada de inmediato: se descuenta del stock
                // disponible (no hasta la entrega), para que no se pueda vender dos veces.
                InventoryMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $item['product_id'],
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'reference_type' => 'customer_order',
                    'reference_id' => $orderId,
                    'user_id' => auth()->id(),
                ]);
            }

            return $orderId;
        });

        return redirect()->route('customerorders.show', $orderId)
            ->with('success', 'Apartado creado correctamente. Stock reservado.');
    }

    public function show(int $order)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $order = $this->findOrderInContext($order, $companyId, $warehouseId);

        $items = DB::table('customer_order_items')
            ->join('products', 'products.id', '=', 'customer_order_items.product_id')
            ->where('customer_order_items.customer_order_id', $order->id)
            ->orderBy('customer_order_items.id')
            ->get([
                'customer_order_items.*',
                'products.name as product_name',
            ]);

        return view('salesquotes::orders.show', compact('order', 'items'));
    }

    public function ready(int $order): RedirectResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $orderData = $this->findOrderInContext($order, $companyId, $warehouseId);

        if ($orderData->status !== 'pendiente') {
            return back()->withErrors('Solo un apartado pendiente puede marcarse como listo para entrega.');
        }

        DB::table('customer_orders')->where('id', $orderData->id)->update([
            'status' => 'listo_para_entrega',
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Apartado marcado como listo para entrega.');
    }

    public function deliver(int $order): RedirectResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $orderData = $this->findOrderInContext($order, $companyId, $warehouseId);

        if (! in_array($orderData->status, ['pendiente', 'listo_para_entrega'], true)) {
            return back()->withErrors('Este apartado ya fue entregado o anulado.');
        }

        DB::table('customer_orders')->where('id', $orderData->id)->update([
            'status' => 'entregado',
            'delivered_at' => now(),
            'delivered_by_user_id' => auth()->id(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Apartado entregado al cliente.');
    }

    public function cancel(int $order): RedirectResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $orderData = $this->findOrderInContext($order, $companyId, $warehouseId);

        if (! $this->isAdminRole()) {
            return back()->withErrors('Solo Admin/SuperAdmin puede anular un apartado.');
        }

        if (! in_array($orderData->status, ['pendiente', 'listo_para_entrega'], true)) {
            return back()->withErrors('Solo un apartado pendiente o listo para entrega puede anularse.');
        }

        DB::transaction(function () use ($orderData, $companyId, $warehouseId) {
            $items = DB::table('customer_order_items')->where('customer_order_id', $orderData->id)->get();

            foreach ($items as $item) {
                InventoryMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'reference_type' => 'customer_order_cancelled',
                    'reference_id' => $orderData->id,
                    'user_id' => auth()->id(),
                ]);
            }

            DB::table('customer_orders')->where('id', $orderData->id)->update([
                'status' => 'cancelado',
                'cancelled_at' => now(),
                'cancelled_by_user_id' => auth()->id(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Apartado anulado. Stock reservado liberado.');
    }
}
