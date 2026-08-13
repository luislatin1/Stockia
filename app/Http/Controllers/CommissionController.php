<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\CompanyUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class CommissionController extends Controller
{
    public function index()
    {
        $companyId = (int) session('current_company_id');

        $vendedores = User::whereHas('companyAccess', function ($query) use ($companyId) {
            $query->where('company_id', $companyId)->where('role', 'Vendedor');
        })
            ->withSum(['commissions as generado' => function ($query) use ($companyId) {
                $query->where('company_id', $companyId)->where('status', 'generada');
            }], 'amount')
            ->withSum(['commissions as pagado' => function ($query) use ($companyId) {
                $query->where('company_id', $companyId)->where('status', 'pagada');
            }], 'amount')
            ->get();

        return view('commissions.index', compact('vendedores'));
    }

    public function show(User $user)
    {
        $companyId = (int) session('current_company_id');

        if (! CompanyUser::where('company_id', $companyId)->where('user_id', $user->id)->exists()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $commissions = Commission::where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->with('sale')
            ->latest()
            ->paginate(25);

        return view('commissions.show', compact('user', 'commissions'));
    }

    public function pay(Commission $commission)
    {
        $companyId = (int) session('current_company_id');

        if ((int) $commission->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($commission->status === 'pagada') {
            abort(Response::HTTP_BAD_REQUEST, 'Esta comisión ya fue pagada.');
        }

        $commission->update([
            'status' => 'pagada',
            'paid_at' => now(),
            'paid_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('commissions.show', $commission->user_id)
            ->with('success', 'Comisión marcada como pagada.');
    }
}
