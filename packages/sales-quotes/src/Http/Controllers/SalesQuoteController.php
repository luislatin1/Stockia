<?php

namespace Stockia\SalesQuotes\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SalesQuoteController extends Controller
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

    private function findQuoteInContext(int $quoteId, int $companyId, int $warehouseId): object
    {
        $query = DB::table('sales_quotes')
            ->leftJoin('users', 'users.id', '=', 'sales_quotes.user_id')
            ->where('sales_quotes.id', $quoteId)
            ->where('sales_quotes.company_id', $companyId)
            ->where('sales_quotes.warehouse_id', $warehouseId);

        if (! $this->isAdminRole()) {
            $query->where('sales_quotes.user_id', auth()->id());
        }

        $quote = $query->first([
            'sales_quotes.*',
            'users.name as user_name',
        ]);

        if (! $quote) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $quote;
    }

    public function index()
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $quotesQuery = DB::table('sales_quotes')
            ->leftJoin('users', 'users.id', '=', 'sales_quotes.user_id')
            ->where('sales_quotes.company_id', $companyId)
            ->where('sales_quotes.warehouse_id', $warehouseId);

        if (! $this->isAdminRole()) {
            $quotesQuery->where('sales_quotes.user_id', auth()->id());
        }

        $quotes = $quotesQuery
            ->orderByDesc('sales_quotes.id')
            ->get([
                'sales_quotes.*',
                'users.name as user_name',
            ]);

        return view('salesquotes::index', compact('quotes'));
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

        return view('salesquotes::create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'valid_until' => ['nullable', 'date'],
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
                return back()->withErrors('Cantidad solicitada supera stock actual para uno o más productos.')->withInput();
            }

            if ($providedPrice !== $currentPrice) {
                return back()->withErrors('El precio cambió en uno o más productos. Actualiza la cotización con el precio vigente.')->withInput();
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

        $subtotal = (float) collect($normalizedItems)->sum('subtotal');
        $subtotal = round($subtotal, 2);
        $taxTotal = round($subtotal * self::IVA_RATE, 2);
        $total = round($subtotal + $taxTotal, 2);

        $quoteId = DB::transaction(function () use ($validated, $normalizedItems, $companyId, $warehouseId, $subtotal, $taxTotal, $total) {
            $quoteId = DB::table('sales_quotes')->insertGetId([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'user_id' => auth()->id(),
                'quote_number' => null,
                'status' => 'draft',
                'customer_name' => trim((string) $validated['customer_name']),
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'valid_until' => $validated['valid_until'] ?? null,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $quoteNumber = 'COT-' . str_pad((string) $quoteId, 6, '0', STR_PAD_LEFT);
            DB::table('sales_quotes')->where('id', $quoteId)->update(['quote_number' => $quoteNumber]);

            foreach ($normalizedItems as $item) {
                DB::table('sales_quote_items')->insert([
                    'sales_quote_id' => $quoteId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'discount_percent' => $item['discount_percent'],
                    'discount_amount' => $item['discount_amount'],
                    'subtotal' => $item['subtotal'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $quoteId;
        });

        return redirect()->route('salesquotes.show', $quoteId)
            ->with('success', 'Cotización creada correctamente.');
    }

    public function show(int $quote)
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $quote = $this->findQuoteInContext($quote, $companyId, $warehouseId);

        $items = DB::table('sales_quote_items')
            ->join('products', 'products.id', '=', 'sales_quote_items.product_id')
            ->where('sales_quote_items.sales_quote_id', $quote->id)
            ->orderBy('sales_quote_items.id')
            ->get([
                'sales_quote_items.*',
                'products.name as product_name',
            ]);

        return view('salesquotes::show', compact('quote', 'items'));
    }

    public function updateStatus(Request $request, int $quote): RedirectResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();
        $quoteData = $this->findQuoteInContext($quote, $companyId, $warehouseId);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,sent,approved,rejected,expired'],
        ]);

        if (! $this->isAdminRole() && ! in_array($validated['status'], ['draft', 'sent'], true)) {
            return back()->withErrors('Solo Admin/SuperAdmin puede aprobar, rechazar o expirar cotizaciones.');
        }

        $payload = [
            'status' => $validated['status'],
            'updated_at' => now(),
        ];

        if ($validated['status'] === 'approved') {
            $payload['approved_at'] = now();
            $payload['approved_by_user_id'] = auth()->id();
        }

        if ($validated['status'] === 'rejected') {
            $payload['rejected_at'] = now();
            $payload['rejected_by_user_id'] = auth()->id();
        }

        DB::table('sales_quotes')
            ->where('id', $quoteData->id)
            ->update($payload);

        return back()->with('success', 'Estado de cotización actualizado.');
    }
}
