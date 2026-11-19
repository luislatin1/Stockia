<?php

namespace Stockia\DteSvMh\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\UbicacionCatalogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stockia\DteSvMh\Models\DteEstablecimiento;
use Stockia\DteSvMh\Models\DtePuntoVenta;

class DteEstablecimientoController extends Controller
{
    public function index(): View
    {
        $establecimientos = DteEstablecimiento::with(['puntosVenta' => fn ($q) => $q->orderBy('codigo')])
            ->orderBy('codigo')
            ->get();

        $tiposEstablecimiento = Schema::hasTable('dte_cat_tipo_establecimiento')
            ? DB::table('dte_cat_tipo_establecimiento')->where('activo', true)->orderBy('codigo')->get(['codigo', 'descripcion'])
            : collect();

        return view('dte::establecimientos.index', [
            'establecimientos' => $establecimientos,
            'tiposEstablecimiento' => $tiposEstablecimiento,
            'departamentos' => UbicacionCatalogo::departamentos(),
            'municipios' => UbicacionCatalogo::municipios(),
            'distritos' => UbicacionCatalogo::distritos(),
            'letras' => DteEstablecimiento::LETRA_POR_TIPO,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateEstablecimiento($request);
        $data['company_id'] = (int) session('current_company_id');

        DteEstablecimiento::create($data);

        return back()->with('success', 'Establecimiento creado.');
    }

    public function update(Request $request, DteEstablecimiento $establecimiento): RedirectResponse
    {
        // Un código ya confirmado con Hacienda no se edita: se desactiva y se crea otro.
        $data = $this->validateEstablecimiento($request, $establecimiento);
        if ($establecimiento->codigo_confirmado) {
            unset($data['codigo']);
        }

        $establecimiento->update($data);

        return back()->with('success', 'Establecimiento actualizado.');
    }

    public function storePuntoVenta(Request $request, DteEstablecimiento $establecimiento): RedirectResponse
    {
        $data = $this->validatePuntoVenta($request, $establecimiento);

        $establecimiento->puntosVenta()->create($data);

        return back()->with('success', 'Punto de venta creado.');
    }

    public function updatePuntoVenta(Request $request, DtePuntoVenta $puntoVenta): RedirectResponse
    {
        $establecimiento = DteEstablecimiento::findOrFail($puntoVenta->establecimiento_id);

        $data = $this->validatePuntoVenta($request, $establecimiento, $puntoVenta);
        if ($puntoVenta->codigo_confirmado) {
            unset($data['codigo']);
        }

        $puntoVenta->update($data);

        return back()->with('success', 'Punto de venta actualizado.');
    }

    private function validateEstablecimiento(Request $request, ?DteEstablecimiento $actual = null): array
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'codigo' => [
                'required', 'string', 'regex:'.DteEstablecimiento::CODIGO_REGEX,
                Rule::unique('dte_establecimientos', 'codigo')
                    ->where('company_id', $companyId)
                    ->ignore($actual?->id),
            ],
            'tipo_establecimiento' => ['required', 'string', Rule::in($this->codigosTipo())],
            'nombre' => ['required', 'string', 'max:255'],
            'departamento' => ['nullable', 'string', 'size:2'],
            'municipio' => ['nullable', 'string', 'size:4'],
            'distrito' => ['nullable', 'string', 'size:2'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'min:8', 'max:30'],
            'correo' => ['nullable', 'email', 'max:120'],
        ], [
            'codigo.regex' => 'El código debe ser una letra M, B, S o P y tres dígitos (ej. M001).',
        ]);

        $this->assertUbicacion($validated);

        $validated['codigo_confirmado'] = $request->boolean('codigo_confirmado');
        $validated['activo'] = $request->boolean('activo', true);

        return $validated;
    }

    private function validatePuntoVenta(Request $request, DteEstablecimiento $establecimiento, ?DtePuntoVenta $actual = null): array
    {
        $validated = $request->validate([
            'codigo' => [
                'required', 'string', 'regex:'.DtePuntoVenta::CODIGO_REGEX,
                Rule::unique('dte_puntos_venta', 'codigo')
                    ->where('establecimiento_id', $establecimiento->id)
                    ->ignore($actual?->id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
        ], [
            'codigo.regex' => 'El código debe ser la letra P y tres dígitos (ej. P001).',
        ]);

        $validated['codigo_confirmado'] = $request->boolean('codigo_confirmado');
        $validated['activo'] = $request->boolean('activo', true);

        return $validated;
    }

    /** Departamento, municipio y distrito deben ser coherentes entre sí. */
    private function assertUbicacion(array $data): void
    {
        $dep = $data['departamento'] ?? null;
        $mun = $data['municipio'] ?? null;
        $dis = $data['distrito'] ?? null;

        if ($mun && (! $dep || ! UbicacionCatalogo::municipios()->contains(fn ($m) => $m->codigo === $mun && $m->departamento_codigo === $dep))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'municipio' => 'El municipio no corresponde al departamento.',
            ]);
        }

        if ($dis && ! UbicacionCatalogo::distritoValido($dep, $mun, $dis)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'distrito' => 'El distrito no corresponde al departamento/municipio.',
            ]);
        }
    }

    /** CAT-009 desde la BD; si el catálogo no está sembrado, los 4 códigos oficiales. */
    private function codigosTipo(): array
    {
        $codigos = Schema::hasTable('dte_cat_tipo_establecimiento')
            ? DB::table('dte_cat_tipo_establecimiento')->where('activo', true)->pluck('codigo')->map(fn ($c) => (string) $c)->all()
            : [];

        return $codigos ?: array_keys(DteEstablecimiento::LETRA_POR_TIPO);
    }
}
