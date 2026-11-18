<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Sales\CreateSale;
use App\Exceptions\SaleValidationException;
use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    private function currentContext(): array
    {
        return [(int) session('current_company_id'), (int) session('current_warehouse_id')];
    }

    private function isAdminRole(): bool
    {
        $role = function_exists('currentRole') ? currentRole() : null;

        return in_array($role, ['Admin', 'SuperAdmin'], true);
    }

    public function index(Request $request): JsonResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $query = Sale::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId);

        if (! $this->isAdminRole()) {
            $query->where('user_id', $request->user()->id);
        }

        $sales = $query->with('items.product')
            ->latest()
            ->paginate((int) $request->query('per_page', 30));

        return SaleResource::collection($sales)->response();
    }

    public function show(Request $request, Sale $sale): JsonResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();

        if ((int) $sale->company_id !== $companyId || (int) $sale->warehouse_id !== $warehouseId) {
            abort(404);
        }

        if (! $this->isAdminRole() && (int) $sale->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $sale->load('items.product');

        return (new SaleResource($sale))->response();
    }

    public function store(Request $request, CreateSale $createSale): JsonResponse
    {
        [$companyId, $warehouseId] = $this->currentContext();

        $validated = $request->validate([
            'products' => ['required', 'array'],
            'products.*' => ['nullable', 'integer', 'min:0'],
            'cash_received' => ['required_unless:es_credito,1', 'nullable', 'numeric', 'min:0'],
            'document_type' => ['required', 'in:ticket,factura'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id', 'required_if:es_credito,1'],
            'tipo_dte' => ['nullable', 'string', 'size:2'],
            'es_credito' => ['nullable', 'boolean'],
            'dias_credito' => ['nullable', 'integer', 'min:1', 'required_if:es_credito,1'],
        ]);

        try {
            $sale = $createSale->handle([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'user_id' => (int) $request->user()->id,
                'products' => $validated['products'] ?? [],
                'document_type' => $validated['document_type'],
                'customer_id' => $validated['customer_id'] ?? null,
                'tipo_dte' => $validated['tipo_dte'] ?? null,
                'cash_received' => $validated['cash_received'] ?? 0,
                'es_credito' => (bool) ($validated['es_credito'] ?? false),
                'dias_credito' => $validated['dias_credito'] ?? null,
            ]);
        } catch (SaleValidationException $e) {
            throw ValidationException::withMessages(['products' => [$e->getMessage()]]);
        }

        $sale->load('items.product');

        return (new SaleResource($sale))->response()->setStatusCode(201);
    }
}
