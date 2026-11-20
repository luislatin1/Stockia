@extends('layouts.app')

@section('title', 'Administración DTE')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Administración DTE</h1>
        <p class="text-sm text-gray-500">Configuración y gestión del flujo fiscal en modo local/dummy.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-gray-500">Clientes DTE</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $customersCount }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-amber-700">DTE pendientes</p>
            <p class="mt-2 text-2xl font-bold text-amber-900">{{ $pendingDtes }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-emerald-700">DTE aceptados</p>
            <p class="mt-2 text-2xl font-bold text-emerald-900">{{ $acceptedDtes }}</p>
        </div>
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-indigo-700">Contingencias abiertas</p>
            <p class="mt-2 text-2xl font-bold text-indigo-900">{{ $contingenciasAbiertas }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <a href="{{ route('dte.customers.index') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300">
            <h2 class="text-lg font-semibold text-gray-900">Clientes (Receptores)</h2>
            <p class="mt-1 text-sm text-gray-500">CRUD completo para documentos CAT-022, NRC y datos fiscales de receptor.</p>
        </a>

        <a href="{{ route('dte.establecimientos.index') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300">
            <h2 class="text-lg font-semibold text-gray-900">Establecimientos y puntos de venta</h2>
            <p class="mt-1 text-sm text-gray-500">Códigos fiscales del número de control (M/B/S/P + 3 dígitos) y su dirección con distrito.</p>
        </a>

        <a href="{{ route('core.admin.index') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300">
            <h2 class="text-lg font-semibold text-gray-900">Emisor y Certificado</h2>
            <p class="mt-1 text-sm text-gray-500">Ajusta datos fiscales del emisor y configuración base de certificado.</p>
        </a>

        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-5">
            <h2 class="text-lg font-semibold text-gray-900">Transmisión MH</h2>
            <p class="mt-1 text-sm text-gray-500">Disponible en modo simulación, contingencia o real según tu configuración.</p>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">DTE SV MH</h2>
        <p class="mt-1 text-sm text-gray-500">Configura modo real o modo estático para pruebas.</p>

        <form method="POST" action="{{ route('dte.admin.settings.update') }}" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
            @csrf
            @method('PATCH')

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Modo integración</label>
                <select name="integration_mode" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="simulacion" @selected(($settings->integration_mode ?? 'simulacion') === 'simulacion')>Simulación (fake MH)</option>
                    <option value="real" @selected(($settings->integration_mode ?? '') === 'real')>Real (MH)</option>
                    <option value="contingencia" @selected(($settings->integration_mode ?? '') === 'contingencia')>Contingencia</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Ambiente</label>
                <input name="ambiente" value="{{ old('ambiente', $settings->ambiente ?? '00') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Numeración del número de control</label>
                <select name="numeracion_modo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="centralizada" @selected(old('numeracion_modo', $settings->numeracion_modo ?? 'centralizada') === 'centralizada')>Centralizada (un consecutivo para toda la empresa)</option>
                    <option value="por_establecimiento" @selected(old('numeracion_modo', $settings->numeracion_modo ?? '') === 'por_establecimiento')>Por establecimiento (consecutivo independiente)</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Nunca por punto de venta ni por tipo de documento. Se reinicia cada ejercicio impositivo.</p>
            </div>

            <div class="flex items-end">
                <a href="{{ route('dte.establecimientos.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                    Administrar establecimientos y puntos de venta &rarr;
                </a>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Auth URL (real)</label>
                <input name="auth_url" value="{{ old('auth_url', $settings->auth_url) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Send URL (real)</label>
                <input name="send_url" value="{{ old('send_url', $settings->send_url) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">URL del firmador (real)</label>
                <input name="signer_url" value="{{ old('signer_url', $settings->signer_url) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div class="md:col-span-2 rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-xs text-indigo-900">
                <strong>Responsable de eventos de invalidación.</strong> Hacienda exige nombre y documento de quien realiza y solicita cada invalidación
                (notas, retornos, facturas anuladas). En modo real es obligatorio; en simulación se usan datos de prueba si está vacío.
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Responsable: nombre completo</label>
                <input name="responsable_nombre" maxlength="100" value="{{ old('responsable_nombre', $settings->responsable_nombre) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Tipo doc.</label>
                    <input name="responsable_tipo_doc" maxlength="2" placeholder="13" value="{{ old('responsable_tipo_doc', $settings->responsable_tipo_doc) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Número de documento</label>
                    <input name="responsable_num_doc" maxlength="20" value="{{ old('responsable_num_doc', $settings->responsable_num_doc) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Usuario API (real)</label>
                <input name="api_user" value="{{ old('api_user', $settings->api_user) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Password API (real)</label>
                <input type="password" name="api_password" value="" autocomplete="new-password"
                       placeholder="{{ $settings->api_password ? 'Guardada. Escriba para reemplazarla.' : '13 a 25 caracteres, con número y carácter especial' }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Contraseña de la llave privada (firmador)</label>
                <input type="password" name="signer_password" value="" autocomplete="new-password"
                       placeholder="{{ $settings->signer_password ? 'Guardada. Escriba para reemplazarla.' : 'La del certificado de firma de Hacienda' }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">El certificado vive en el firmador de este cliente, no en Stockia.</p>
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="use_dummy_certificate" value="1" {{ old('use_dummy_certificate', $settings->use_dummy_certificate) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Usar certificado dummy si no hay certificado real</span>
                </label>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Certificado dummy (texto)</label>
                <textarea name="dummy_certificate_text" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('dummy_certificate_text', $settings->dummy_certificate_text) }}</textarea>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Token simulado</label>
                <input name="static_token" value="{{ old('static_token', $settings->static_token) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Sello simulado</label>
                <input name="static_sello" value="{{ old('static_sello', $settings->static_sello) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Estado simulado</label>
                <select name="static_estado" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach(['PENDIENTE', 'ACEPTADO', 'RECHAZADO', 'INVALIDADO'] as $estado)
                        <option value="{{ $estado }}" @selected(old('static_estado', $settings->static_estado) === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Respuesta simulada JSON (opcional)</label>
                <textarea name="static_response_json" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder='{"estado":"ACEPTADO","mensaje":"Simulado"}'>{{ old('static_response_json', $settings->static_response ? json_encode($settings->static_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="enabled" value="1" {{ old('enabled', $settings->enabled) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Habilitar emisión DTE automática</span>
                </label>
            </div>

            <div class="md:col-span-2">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Guardar Configuración DTE
                </button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Últimos DTE generados</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Número Control</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lastDtes as $dte)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $dte->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $dte->tipo_dte }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $dte->numero_control }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $dte->estado }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ \Illuminate\Support\Carbon::parse($dte->created_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Aún no hay DTE generados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
