<?php

namespace App\Http\Controllers;

use App\Models\DteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DteAdminController extends Controller
{
    public function index(): View
    {
        $companyId = (int) session('current_company_id');

        $customersCount = Schema::hasTable('customers')
            ? (int) DB::table('customers')->where('company_id', $companyId)->count()
            : 0;

        $pendingDtes = Schema::hasTable('dtes')
            ? (int) DB::table('dtes')->where('company_id', $companyId)->where('estado', 'PENDIENTE')->count()
            : 0;

        $acceptedDtes = Schema::hasTable('dtes')
            ? (int) DB::table('dtes')->where('company_id', $companyId)->where('estado', 'ACEPTADO')->count()
            : 0;

        $contingenciasAbiertas = Schema::hasTable('dte_contingencias')
            ? (int) DB::table('dte_contingencias')->where('company_id', $companyId)->where('estado', 'ABIERTA')->count()
            : 0;

        $settings = DteSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'enabled' => true,
                'integration_mode' => config('dte.mode', 'simulacion'),
                'ambiente' => '00',
                'establecimiento' => '0001',
                'punto_venta' => '0001',
                'use_dummy_certificate' => true,
                'dummy_certificate_text' => 'CERTIFICADO-DUMMY',
                'static_token' => 'TOKEN-DUMMY',
                'static_sello' => 'SELLO-DUMMY',
                'static_estado' => 'ACEPTADO',
            ]
        );

        if ($settings->integration_mode === 'static') {
            $settings->update(['integration_mode' => 'simulacion']);
            $settings->refresh();
        }

        $lastDtes = Schema::hasTable('dtes')
            ? DB::table('dtes')
                ->where('company_id', $companyId)
                ->latest('id')
                ->limit(8)
                ->get(['id', 'tipo_dte', 'numero_control', 'estado', 'created_at'])
            : collect();

        return view('dte.admin.index', compact(
            'customersCount',
            'pendingDtes',
            'acceptedDtes',
            'contingenciasAbiertas',
            'settings',
            'lastDtes',
        ));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'integration_mode' => ['required', 'in:simulacion,real,contingencia'],
            'ambiente' => ['required', 'string', 'size:2'],
            'establecimiento' => ['required', 'string', 'max:4'],
            'punto_venta' => ['required', 'string', 'max:4'],
            'api_user' => ['nullable', 'string', 'max:255'],
            'api_password' => ['nullable', 'string', 'max:2000'],
            'auth_url' => ['nullable', 'url', 'max:500'],
            'send_url' => ['nullable', 'url', 'max:500'],
            'signer_url' => ['nullable', 'url', 'max:500'],
            'use_dummy_certificate' => ['nullable', 'boolean'],
            'dummy_certificate_text' => ['nullable', 'string', 'max:10000'],
            'static_token' => ['nullable', 'string', 'max:255'],
            'static_sello' => ['nullable', 'string', 'max:255'],
            'static_estado' => ['nullable', 'in:PENDIENTE,ACEPTADO,RECHAZADO,INVALIDADO'],
            'static_response_json' => ['nullable', 'string'],
        ]);

        $staticResponse = null;
        $rawJson = trim((string) ($validated['static_response_json'] ?? ''));
        if ($rawJson !== '') {
            $decoded = json_decode($rawJson, true);
            if (! is_array($decoded)) {
                return back()->withErrors('JSON estático inválido.')->withInput();
            }
            $staticResponse = $decoded;
        }

        DteSetting::updateOrCreate(
            ['company_id' => $companyId],
            [
                'enabled' => $request->boolean('enabled', true),
                'integration_mode' => $validated['integration_mode'],
                'ambiente' => $validated['ambiente'],
                'establecimiento' => str_pad((string) $validated['establecimiento'], 4, '0', STR_PAD_LEFT),
                'punto_venta' => str_pad((string) $validated['punto_venta'], 4, '0', STR_PAD_LEFT),
                'api_user' => $validated['api_user'] ?? null,
                'api_password' => $validated['api_password'] ?? null,
                'auth_url' => $validated['auth_url'] ?? null,
                'send_url' => $validated['send_url'] ?? null,
                'signer_url' => $validated['signer_url'] ?? null,
                'use_dummy_certificate' => $request->boolean('use_dummy_certificate', false),
                'dummy_certificate_text' => $validated['dummy_certificate_text'] ?? null,
                'static_token' => $validated['static_token'] ?? null,
                'static_sello' => $validated['static_sello'] ?? null,
                'static_estado' => $validated['static_estado'] ?? 'ACEPTADO',
                'static_response' => $staticResponse,
            ]
        );

        return back()->with('success', 'Configuración DTE actualizada.');
    }
}
