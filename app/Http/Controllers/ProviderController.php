<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ProviderController extends Controller
{
    private function currentCompanyId(): int
    {
        $companyId = (int) session('current_company_id');

        if (! $companyId) {
            abort(Response::HTTP_BAD_REQUEST, 'Empresa no seleccionada.');
        }

        return $companyId;
    }

    private function rules(int $companyId, ?int $ignoreId = null): array
    {
        $uniqueNombre = Rule::unique('providers', 'nombre')->where(fn ($query) => $query->where('company_id', $companyId));

        if ($ignoreId) {
            $uniqueNombre = $uniqueNombre->ignore($ignoreId);
        }

        return [
            'nombre' => ['required', 'string', 'max:45', $uniqueNombre],
            'nit' => 'nullable|string|max:20',
            'nrc' => 'nullable|string|max:20',
            'dui' => 'nullable|string|max:15',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:255',
        ];
    }

    public function index()
    {
        $companyId = $this->currentCompanyId();

        $providers = Provider::withCount('products')
            ->where('company_id', $companyId)
            ->orderBy('nombre')
            ->paginate(20);

        return view('providers.index', compact('providers'));
    }

    public function create()
    {
        $this->currentCompanyId();

        return view('providers.create');
    }

    public function store(Request $request)
    {
        $companyId = $this->currentCompanyId();

        $validated = $request->validate($this->rules($companyId));

        Provider::create($validated + ['company_id' => $companyId]);

        return redirect()->route('providers.index')->with('success', 'Proveedor creado correctamente.');
    }

    public function edit(Provider $provider)
    {
        $companyId = $this->currentCompanyId();

        if ((int) $provider->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return view('providers.edit', compact('provider'));
    }

    public function update(Request $request, Provider $provider)
    {
        $companyId = $this->currentCompanyId();

        if ((int) $provider->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate($this->rules($companyId, $provider->id));

        $provider->update($validated);

        return redirect()->route('providers.index')->with('success', 'Proveedor actualizado.');
    }

    public function destroy(Provider $provider)
    {
        $companyId = $this->currentCompanyId();

        if ((int) $provider->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $provider->delete();

        return redirect()->route('providers.index')->with('success', 'Proveedor eliminado.');
    }
}
