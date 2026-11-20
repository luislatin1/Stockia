<?php

namespace Stockia\DteSvMh\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Stockia\DteSvMh\Models\DteSetting;

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

        return view('dte::admin.index', compact(
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
            'numeracion_modo' => ['required', 'in:centralizada,por_establecimiento'],
            'api_user' => ['nullable', 'string', 'max:255'],
            'api_password' => ['nullable', 'string', 'max:2000'],
            'auth_url' => ['nullable', 'url', 'max:500'],
            'send_url' => ['nullable', 'url', 'max:500'],
            'signer_url' => ['nullable', 'url', 'max:500'],
            'signer_password' => ['nullable', 'string', 'max:500'],
            'use_dummy_certificate' => ['nullable', 'boolean'],
            'dummy_certificate_text' => ['nullable', 'string', 'max:10000'],
            'static_token' => ['nullable', 'string', 'max:255'],
            'static_sello' => ['nullable', 'string', 'max:255'],
            'static_estado' => ['nullable', 'in:PENDIENTE,ACEPTADO,RECHAZADO,INVALIDADO'],
            'static_response_json' => ['nullable', 'string'],
            // Quien realiza/solicita los eventos de invalidación (CAT-022: 13 DUI, 36 NIT, 03 pasaporte, 02 carnet, 37 otro).
            'responsable_nombre' => ['nullable', 'string', 'max:100', 'required_with:responsable_num_doc'],
            'responsable_tipo_doc' => ['nullable', 'string', 'size:2', 'required_with:responsable_num_doc'],
            'responsable_num_doc' => ['nullable', 'string', 'max:20', 'required_with:responsable_nombre'],
            // Factura de Exportación: 1 bienes (exige recinto, tipo de régimen y régimen) o 2 servicios.
            'expo_tipo_item' => ['nullable', 'integer', 'in:1,2'],
            'expo_recinto' => ['nullable', 'string', 'size:2'],
            'expo_tipo_regimen' => ['nullable', 'string', 'in:EX-1,EX-2,EX-3,TA-1'],
            'expo_regimen' => ['nullable', 'string', 'max:13'],
            'expo_cod_incoterms' => ['nullable', 'string', 'size:2'],
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

        // Un campo de contraseña vacío significa "conservar la actual", no "borrarla".
        $secrets = array_filter([
            'api_password' => $validated['api_password'] ?? null,
            'signer_password' => $validated['signer_password'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        DteSetting::updateOrCreate(
            ['company_id' => $companyId],
            $secrets + [
                'enabled' => $request->boolean('enabled', true),
                'integration_mode' => $validated['integration_mode'],
                'ambiente' => $validated['ambiente'],
                'numeracion_modo' => $validated['numeracion_modo'],
                'api_user' => $validated['api_user'] ?? null,
                'auth_url' => $validated['auth_url'] ?? null,
                'send_url' => $validated['send_url'] ?? null,
                'signer_url' => $validated['signer_url'] ?? null,
                'use_dummy_certificate' => $request->boolean('use_dummy_certificate', false),
                'dummy_certificate_text' => $validated['dummy_certificate_text'] ?? null,
                'static_token' => $validated['static_token'] ?? null,
                'static_sello' => $validated['static_sello'] ?? null,
                'static_estado' => $validated['static_estado'] ?? 'ACEPTADO',
                'static_response' => $staticResponse,
                'responsable_nombre' => $validated['responsable_nombre'] ?? null,
                'responsable_tipo_doc' => $validated['responsable_tipo_doc'] ?? null,
                'responsable_num_doc' => $validated['responsable_num_doc'] ?? null,
                'expo_tipo_item' => $validated['expo_tipo_item'] ?? null,
                'expo_recinto' => $validated['expo_recinto'] ?? null,
                'expo_tipo_regimen' => $validated['expo_tipo_regimen'] ?? null,
                'expo_regimen' => $validated['expo_regimen'] ?? null,
                'expo_cod_incoterms' => $validated['expo_cod_incoterms'] ?? null,
            ]
        );

        return back()->with('success', 'Configuración DTE actualizada.');
    }
}
