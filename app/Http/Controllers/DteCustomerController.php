<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DteCustomerController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = (int) session('current_company_id');
        $search = trim((string) $request->query('q', ''));

        $query = Customer::query()
            ->where('company_id', $companyId)
            ->orderBy('nombre');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%")
                    ->orWhere('nrc', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(20)->withQueryString();

        return view('dte.customers.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        [$departments, $municipalities, $documentTypes] = $this->catalogs();

        return view('dte.customers.create', compact('departments', 'municipalities', 'documentTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = (int) session('current_company_id');
        $validated = $this->validateRequest($request, $companyId);
        $validated['es_contribuyente'] = $request->boolean('es_contribuyente');

        if (! empty($validated['es_contribuyente']) && empty($validated['nrc'])) {
            return back()->withErrors('NRC es obligatorio para contribuyentes.')->withInput();
        }

        Customer::create([
            ...$validated,
            'company_id' => $companyId,
        ]);

        return redirect()->route('dte.customers.index')
            ->with('success', 'Cliente DTE creado correctamente.');
    }

    public function show(Customer $customer): View
    {
        $this->ensureCompanyContext($customer);

        return view('dte.customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        $this->ensureCompanyContext($customer);
        [$departments, $municipalities, $documentTypes] = $this->catalogs();

        return view('dte.customers.edit', compact('customer', 'departments', 'municipalities', 'documentTypes'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->ensureCompanyContext($customer);
        $companyId = (int) session('current_company_id');
        $validated = $this->validateRequest($request, $companyId, $customer->id);
        $validated['es_contribuyente'] = $request->boolean('es_contribuyente');

        if (! empty($validated['es_contribuyente']) && empty($validated['nrc'])) {
            return back()->withErrors('NRC es obligatorio para contribuyentes.')->withInput();
        }

        $customer->update($validated);

        return redirect()->route('dte.customers.index')
            ->with('success', 'Cliente DTE actualizado.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->ensureCompanyContext($customer);

        DB::transaction(function () use ($customer) {
            DB::table('sales')
                ->where('customer_id', $customer->id)
                ->update(['customer_id' => null]);

            $customer->delete();
        });

        return redirect()->route('dte.customers.index')
            ->with('success', 'Cliente DTE eliminado.');
    }

    private function validateRequest(Request $request, int $companyId, ?int $ignoreId = null): array
    {
        $uniqueDoc = Rule::unique('customers', 'numero_documento')
            ->where(fn ($q) => $q->where('company_id', $companyId)->where('tipo_documento', $request->input('tipo_documento')));

        if ($ignoreId) {
            $uniqueDoc = $uniqueDoc->ignore($ignoreId);
        }

        return $request->validate([
            'tipo_documento' => ['required', 'string', 'size:2', Rule::in($this->availableDocumentTypeCodes())],
            'numero_documento' => ['required', 'string', 'max:20', $uniqueDoc],
            'nrc' => ['nullable', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:255'],
            'departamento' => ['nullable', 'string', 'max:2'],
            'municipio' => ['nullable', 'string', 'max:4'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:120'],
            'es_contribuyente' => ['nullable', 'boolean'],
        ]);
    }

    private function ensureCompanyContext(Customer $customer): void
    {
        $companyId = (int) session('current_company_id');
        abort_if((int) $customer->company_id !== $companyId, 404);
    }

    private function catalogs(): array
    {
        $departments = [];
        $municipalities = [];
        $documentTypes = [];

        if (Schema::hasTable('dte_departamentos')) {
            $departments = DB::table('dte_departamentos')
                ->where('activo', true)
                ->orderBy('codigo')
                ->get(['codigo', 'nombre']);
        }

        if (Schema::hasTable('dte_municipios')) {
            $municipalities = DB::table('dte_municipios')
                ->where('activo', true)
                ->orderBy('codigo')
                ->get(['codigo', 'departamento_codigo', 'nombre'])
                ->map(function ($row) {
                    $row->codigo_local = substr((string) $row->codigo, -2);
                    return $row;
                });
        }

        if (Schema::hasTable('dte_cat_022_tipos_documento')) {
            $documentTypes = DB::table('dte_cat_022_tipos_documento')
                ->where('activo', true)
                ->orderBy('codigo')
                ->get(['codigo', 'descripcion']);
        }

        return [$departments, $municipalities, $documentTypes];
    }

    private function availableDocumentTypeCodes(): array
    {
        if (! Schema::hasTable('dte_cat_022_tipos_documento')) {
            return ['13', '36', '03', '02', '37'];
        }

        $codes = DB::table('dte_cat_022_tipos_documento')
            ->where('activo', true)
            ->pluck('codigo')
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();

        return ! empty($codes) ? $codes : ['13', '36', '03', '02', '37'];
    }
}
