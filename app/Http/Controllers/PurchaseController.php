<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\LegacyPurchase;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PurchaseController extends Controller
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

    public function index(Request $request)
    {
        [$companyId] = $this->currentContext();

        $view = $request->query('view') === 'historico' ? 'historico' : 'live';

        if ($view === 'historico') {
            $purchases = LegacyPurchase::with('provider')
                ->where('company_id', $companyId)
                ->latest('fecha')
                ->latest('id')
                ->paginate(20);
        } else {
            $purchases = Purchase::with('provider')
                ->where('company_id', $companyId)
                ->latest('fecha')
                ->latest('id')
                ->paginate(20);
        }

        return view('purchases.index', compact('purchases', 'view'));
    }

    public function create()
    {
        [$companyId] = $this->currentContext();

        $providers = Provider::where('company_id', $companyId)->orderBy('nombre')->get();
        $products = Product::where('company_id', $companyId)->orderBy('name')->get(['id', 'name', 'sku', 'barcode']);

        return view('purchases.create', compact('providers', 'products'));
    }

    public function store(Request $request)
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $validated = $request->validate([
            'provider_id' => [
                'nullable',
                Rule::exists('providers', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'numero_doc' => 'nullable|string|max:30',
            'fecha' => 'required|date',
            'notes' => 'nullable|string|max:255',
            'es_credito' => 'nullable|boolean',
            'dias_credito' => 'nullable|integer|min:1|required_if:es_credito,1',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.costo_unitario' => 'required|numeric|min:0',
        ]);

        // El desglose gravado/exento se deriva del campo afecto_iva de cada producto
        // (el mismo dato que ya usan las Ventas), no de una entrada manual nueva en el
        // formulario: así el Libro de Compras queda correcto sin agregar pasos al usuario.
        $afectoIvaMap = Product::where('company_id', $companyId)
            ->whereIn('id', collect($validated['items'])->pluck('product_id'))
            ->pluck('afecto_iva', 'id');

        $gravadas = 0.0;
        $exentas = 0.0;

        foreach ($validated['items'] as $item) {
            $lineSubtotal = $item['cantidad'] * $item['costo_unitario'];

            if ($afectoIvaMap[$item['product_id']] ?? true) {
                $gravadas += $lineSubtotal;
            } else {
                $exentas += $lineSubtotal;
            }
        }

        $gravadas = round($gravadas, 2);
        $exentas = round($exentas, 2);
        $subtotal = round($gravadas + $exentas, 2);
        $iva = round($gravadas * 0.13, 2);
        $total = round($subtotal + $iva, 2);

        $esCredito = (bool) ($validated['es_credito'] ?? false);
        $diasCredito = $esCredito ? (int) $validated['dias_credito'] : null;
        $fechaVencimiento = $esCredito
            ? Carbon::parse($validated['fecha'])->addDays($diasCredito)
            : null;

        $purchaseId = DB::transaction(function () use ($validated, $companyId, $warehouseId, $subtotal, $gravadas, $exentas, $iva, $total, $esCredito, $diasCredito, $fechaVencimiento) {
            $purchase = Purchase::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'provider_id' => $validated['provider_id'] ?? null,
                'user_id' => auth()->id(),
                'numero_doc' => $validated['numero_doc'] ?? null,
                'fecha' => $validated['fecha'],
                'subtotal' => $subtotal,
                'gravadas' => $gravadas,
                'exentas' => $exentas,
                'no_sujetas' => 0,
                'iva' => $iva,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
                'es_credito' => $esCredito,
                'dias_credito' => $diasCredito,
                'fecha_vencimiento' => $fechaVencimiento,
                'saldo_pendiente' => $esCredito ? $total : 0,
            ]);

            foreach ($validated['items'] as $item) {
                $lineSubtotal = $item['cantidad'] * $item['costo_unitario'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'cantidad' => $item['cantidad'],
                    'costo_unitario' => $item['costo_unitario'],
                    'subtotal' => $lineSubtotal,
                ]);

                InventoryMovement::create([
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $item['product_id'],
                    'type' => 'in',
                    'quantity' => $item['cantidad'],
                    'reference_type' => 'purchase',
                    'reference_id' => $purchase->id,
                    'user_id' => auth()->id(),
                ]);

                DB::table('product_warehouse')
                    ->where('company_id', $companyId)
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $warehouseId)
                    ->update(['cost' => $item['costo_unitario'], 'updated_at' => now()]);
            }

            return $purchase->id;
        });

        return redirect()->route('purchases.show', $purchaseId)
            ->with('success', 'Compra registrada correctamente.');
    }

    public function show(Purchase $purchase)
    {
        [$companyId] = $this->currentContext();

        if ((int) $purchase->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $purchase->load(['items.product', 'provider', 'payments.user']);

        return view('purchases.show', compact('purchase'));
    }

    public function storePayment(Request $request, Purchase $purchase)
    {
        [$companyId] = $this->currentContext();

        if ((int) $purchase->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if (! $purchase->es_credito || $purchase->saldo_pendiente <= 0) {
            abort(Response::HTTP_BAD_REQUEST, 'Esta compra no tiene saldo pendiente.');
        }

        $validated = $request->validate([
            'fecha' => 'required|date',
            'monto' => ['required', 'numeric', 'min:0.01', 'max:'.$purchase->saldo_pendiente],
            'forma_pago' => 'required|string|max:30',
            'referencia' => 'nullable|string|max:60',
            'notes' => 'nullable|string|max:255',
        ]);

        PurchasePayment::create([
            'company_id' => $companyId,
            'purchase_id' => $purchase->id,
            'user_id' => auth()->id(),
            'fecha' => $validated['fecha'],
            'monto' => $validated['monto'],
            'forma_pago' => $validated['forma_pago'],
            'referencia' => $validated['referencia'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Abono registrado correctamente.');
    }
}
