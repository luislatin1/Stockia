<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->forget(['current_company_id', 'current_warehouse_id']);

        return redirect()->route('company.select');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if (Schema::hasTable('pos_sessions')) {
            $openSession = DB::table('pos_sessions')
                ->where('user_id', (int) optional($request->user())->id)
                ->whereNull('closed_at')
                ->orderByDesc('id')
                ->first();

            if ($openSession) {
                if (Route::has('ptvpos.close')) {
                    return redirect()->route('ptvpos.close')
                        ->with('error', 'Debes cerrar caja antes de cerrar sesion.');
                }

                return back()->with('error', 'Debes cerrar caja antes de cerrar sesion.');
            }
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
