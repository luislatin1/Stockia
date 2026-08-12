<?php

namespace App\Http\Controllers;

use App\Models\Warranty;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WarrantyController extends Controller
{
    private function currentCompanyId(): int
    {
        return (int) session('current_company_id');
    }

    private function ensureAccess(Warranty $warranty): void
    {
        if ((int) $warranty->company_id !== $this->currentCompanyId()) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    public function index(Request $request)
    {
        $companyId = $this->currentCompanyId();

        $query = Warranty::where('company_id', $companyId)->with(['product', 'customer']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->query('estado'));
        }

        $warranties = $query->latest()->get();

        return view('warranties.index', compact('warranties'));
    }

    public function show(Warranty $warranty)
    {
        $this->ensureAccess($warranty);
        $warranty->load(['product', 'customer', 'sale']);

        return view('warranties.show', compact('warranty'));
    }

    public function claim(Request $request, Warranty $warranty)
    {
        $this->ensureAccess($warranty);

        if ($warranty->estado !== 'vigente') {
            abort(Response::HTTP_BAD_REQUEST, 'Solo una garantía vigente puede reclamarse.');
        }

        $validated = $request->validate([
            'descripcion_falla' => ['required', 'string', 'max:500'],
        ]);

        $warranty->update([
            'estado' => 'reclamada',
            'fecha_reclamo' => now()->toDateString(),
            'descripcion_falla' => $validated['descripcion_falla'],
        ]);

        return redirect()->route('warranties.show', $warranty)
            ->with('success', 'Reclamo registrado correctamente.');
    }

    public function resolve(Request $request, Warranty $warranty)
    {
        $this->ensureAccess($warranty);

        if ($warranty->estado !== 'reclamada') {
            abort(Response::HTTP_BAD_REQUEST, 'Solo una garantía reclamada puede resolverse.');
        }

        $validated = $request->validate([
            'resolucion' => ['required', 'string', 'max:500'],
        ]);

        $warranty->update([
            'estado' => 'resuelta',
            'resolucion' => $validated['resolucion'],
        ]);

        return redirect()->route('warranties.show', $warranty)
            ->with('success', 'Garantía marcada como resuelta.');
    }
}
