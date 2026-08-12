<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    private function currentCompanyId(): int
    {
        $companyId = (int) session('current_company_id');

        if (! $companyId) {
            abort(Response::HTTP_BAD_REQUEST, 'Empresa no seleccionada.');
        }

        return $companyId;
    }

    public function index()
    {
        $companyId = $this->currentCompanyId();

        $categories = Category::withCount('products')
            ->where('company_id', $companyId)
            ->orderBy('nombre')
            ->paginate(20);

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        $this->currentCompanyId();

        return view('categories.create');
    }

    public function store(Request $request)
    {
        $companyId = $this->currentCompanyId();

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'nombre')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'descripcion' => 'nullable|string|max:150',
        ]);

        Category::create([
            'company_id' => $companyId,
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoría creada correctamente.');
    }

    public function edit(Category $category)
    {
        $companyId = $this->currentCompanyId();

        if ((int) $category->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $companyId = $this->currentCompanyId();

        if ((int) $category->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'nombre')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($category->id),
            ],
            'descripcion' => 'nullable|string|max:150',
        ]);

        $category->update([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoría actualizada.');
    }

    public function destroy(Category $category)
    {
        $companyId = $this->currentCompanyId();

        if ((int) $category->company_id !== $companyId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Categoría eliminada.');
    }
}
