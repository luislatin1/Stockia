<?php

namespace Stockia\PTVPos\Http\Controllers\Api;

use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Versión API de lo mínimo de ptv-pos: consultar si hay una caja abierta y
 * abrirla. Reusa las mismas tablas (pos_registers/pos_sessions) y las mismas
 * reglas de negocio que PTVPosController (web), sin arrastrar aún el
 * checkout/cierre — eso sigue siendo solo web hasta que se decida extender
 * este mismo molde (ver HANDOFF_API.md).
 */
class PTVPosApiController extends Controller
{
    private function currentContext(Request $request): array
    {
        return [
            (int) session('current_company_id'),
            (int) session('current_warehouse_id'),
            (int) $request->user()->id,
        ];
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

    public function session(Request $request): JsonResponse
    {
        [$companyId, $warehouseId, $userId] = $this->currentContext($request);

        $session = $this->getActiveSession($companyId, $warehouseId, $userId);

        if (! $session) {
            return response()->json(['has_open_session' => false]);
        }

        $salesTotal = (float) Sale::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('user_id', $userId)
            ->whereIn('status', ['completed', 'pending_print'])
            ->where('created_at', '>=', $session->opened_at)
            ->sum('total');

        $manualIn = (float) DB::table('pos_cash_movements')
            ->where('pos_session_id', $session->id)->where('type', 'in')->sum('amount');
        $manualOut = (float) DB::table('pos_cash_movements')
            ->where('pos_session_id', $session->id)->where('type', 'out')->sum('amount');

        return response()->json([
            'has_open_session' => true,
            'session' => [
                'id' => $session->id,
                'register_id' => $session->register_id,
                'register_name' => $session->register_name,
                'opened_at' => $session->opened_at,
                'opening_cash' => (float) $session->opening_cash,
                'expected_cash' => round((float) $session->opening_cash + $salesTotal + $manualIn - $manualOut, 2),
            ],
        ]);
    }

    public function openSession(Request $request): JsonResponse
    {
        [$companyId, $warehouseId, $userId] = $this->currentContext($request);

        $validated = $request->validate([
            'register_id' => ['required', 'integer'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $register = DB::table('pos_registers')
            ->where('id', $validated['register_id'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (! $register) {
            return response()->json(['message' => 'La caja seleccionada no es válida o está inactiva.'], 422);
        }

        if ($this->getActiveSession($companyId, $warehouseId, $userId)) {
            return response()->json(['message' => 'Ya tienes una caja abierta. Debes cerrarla antes de abrir otra.'], 422);
        }

        $registerBusy = DB::table('pos_sessions')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('register_id', $validated['register_id'])
            ->whereNull('closed_at')
            ->exists();

        if ($registerBusy) {
            return response()->json(['message' => 'La caja seleccionada ya está abierta por otro usuario.'], 422);
        }

        $sessionId = DB::table('pos_sessions')->insertGetId([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'user_id' => $userId,
            'register_id' => $validated['register_id'],
            'opening_cash' => $validated['opening_cash'],
            'opened_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['session_id' => $sessionId], 201);
    }
}
