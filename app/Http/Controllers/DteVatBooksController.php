<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DteVatBooksController extends Controller
{
    private const BOOKS = ['contribuyente', 'consumidor', 'compras'];

    private function period(Request $request): array
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        return [$year, $month];
    }

    private function ventasContribuyente(int $companyId, int $year, int $month)
    {
        return DB::table('sales as s')
            ->leftJoin('customers as c', 'c.id', '=', 's.customer_id')
            ->where('s.company_id', $companyId)
            ->where('s.status', 'completed')
            ->where('s.tipo_dte', '03')
            ->whereYear('s.created_at', $year)
            ->whereMonth('s.created_at', $month)
            ->orderBy('s.created_at')
            ->get([
                's.id',
                's.created_at',
                's.numero_interno',
                'c.nombre as cliente_nombre',
                'c.tipo_documento as cliente_tipo_documento',
                'c.numero_documento as cliente_numero_documento',
                'c.nrc as cliente_nrc',
                's.gravadas',
                's.exentas',
                's.no_sujetas',
                's.iva',
                's.total',
            ]);
    }

    private function ventasConsumidor(int $companyId, int $year, int $month)
    {
        return DB::table('sales')
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereNotNull('tipo_dte')
            ->where('tipo_dte', '!=', '03')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw(
                'DATE(created_at) as fecha,
                MIN(numero_interno) as primer_documento,
                MAX(numero_interno) as ultimo_documento,
                COUNT(*) as cantidad_documentos,
                SUM(gravadas) as gravadas,
                SUM(exentas) as exentas,
                SUM(no_sujetas) as no_sujetas,
                SUM(iva) as iva,
                SUM(total) as total'
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('fecha')
            ->get();
    }

    private function compras(int $companyId, int $year, int $month)
    {
        return DB::table('purchases as p')
            ->leftJoin('providers as pr', 'pr.id', '=', 'p.provider_id')
            ->where('p.company_id', $companyId)
            ->whereYear('p.fecha', $year)
            ->whereMonth('p.fecha', $month)
            ->orderBy('p.fecha')
            ->get([
                'p.id',
                'p.fecha',
                'p.numero_doc',
                'pr.nombre as proveedor_nombre',
                'pr.nit as proveedor_nit',
                'pr.nrc as proveedor_nrc',
                'p.gravadas',
                'p.exentas',
                'p.no_sujetas',
                'p.iva',
                'p.total',
            ]);
    }

    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');
        [$year, $month] = $this->period($request);

        $ventasContribuyente = $this->ventasContribuyente($companyId, $year, $month);
        $ventasConsumidor = $this->ventasConsumidor($companyId, $year, $month);
        $compras = $this->compras($companyId, $year, $month);

        $totales = [
            'contribuyente' => [
                'gravadas' => (float) $ventasContribuyente->sum('gravadas'),
                'exentas' => (float) $ventasContribuyente->sum('exentas'),
                'no_sujetas' => (float) $ventasContribuyente->sum('no_sujetas'),
                'iva' => (float) $ventasContribuyente->sum('iva'),
                'total' => (float) $ventasContribuyente->sum('total'),
            ],
            'consumidor' => [
                'gravadas' => (float) $ventasConsumidor->sum('gravadas'),
                'exentas' => (float) $ventasConsumidor->sum('exentas'),
                'no_sujetas' => (float) $ventasConsumidor->sum('no_sujetas'),
                'iva' => (float) $ventasConsumidor->sum('iva'),
                'total' => (float) $ventasConsumidor->sum('total'),
            ],
            'compras' => [
                'gravadas' => (float) $compras->sum('gravadas'),
                'exentas' => (float) $compras->sum('exentas'),
                'no_sujetas' => (float) $compras->sum('no_sujetas'),
                'iva' => (float) $compras->sum('iva'),
                'total' => (float) $compras->sum('total'),
            ],
        ];

        return view('dte.books.index', compact(
            'ventasContribuyente',
            'ventasConsumidor',
            'compras',
            'totales',
            'year',
            'month'
        ));
    }

    public function export(Request $request, string $book): StreamedResponse
    {
        if (! in_array($book, self::BOOKS, true)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $companyId = (int) session('current_company_id');
        [$year, $month] = $this->period($request);
        $periodo = sprintf('%04d-%02d', $year, $month);
        $filename = "libro_{$book}_{$companyId}_{$periodo}.csv";

        return response()->streamDownload(function () use ($book, $companyId, $year, $month): void {
            $output = fopen('php://output', 'w');
            if (! $output) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");

            if ($book === 'contribuyente') {
                fputcsv($output, ['Fecha', 'Documento', 'Cliente', 'Tipo Doc.', 'No. Documento', 'NRC', 'Gravadas', 'Exentas', 'No Sujetas', 'IVA', 'Total'], ';');
                foreach ($this->ventasContribuyente($companyId, $year, $month) as $row) {
                    fputcsv($output, [
                        optional(Date::parse($row->created_at))->format('Y-m-d'),
                        $row->numero_interno,
                        $row->cliente_nombre ?: 'N/A',
                        $row->cliente_tipo_documento ?: '',
                        $row->cliente_numero_documento ?: '',
                        $row->cliente_nrc ?: '',
                        number_format((float) $row->gravadas, 2, '.', ''),
                        number_format((float) $row->exentas, 2, '.', ''),
                        number_format((float) $row->no_sujetas, 2, '.', ''),
                        number_format((float) $row->iva, 2, '.', ''),
                        number_format((float) $row->total, 2, '.', ''),
                    ], ';');
                }
            } elseif ($book === 'consumidor') {
                fputcsv($output, ['Fecha', 'Del documento', 'Al documento', 'Cantidad', 'Gravadas', 'Exentas', 'No Sujetas', 'IVA', 'Total'], ';');
                foreach ($this->ventasConsumidor($companyId, $year, $month) as $row) {
                    fputcsv($output, [
                        $row->fecha,
                        $row->primer_documento,
                        $row->ultimo_documento,
                        $row->cantidad_documentos,
                        number_format((float) $row->gravadas, 2, '.', ''),
                        number_format((float) $row->exentas, 2, '.', ''),
                        number_format((float) $row->no_sujetas, 2, '.', ''),
                        number_format((float) $row->iva, 2, '.', ''),
                        number_format((float) $row->total, 2, '.', ''),
                    ], ';');
                }
            } else {
                fputcsv($output, ['Fecha', 'No. Documento', 'Proveedor', 'NIT', 'NRC', 'Compras Gravadas', 'Compras Exentas', 'No Sujetas', 'IVA', 'Total'], ';');
                foreach ($this->compras($companyId, $year, $month) as $row) {
                    fputcsv($output, [
                        optional(Date::parse($row->fecha))->format('Y-m-d'),
                        $row->numero_doc,
                        $row->proveedor_nombre ?: 'N/A',
                        $row->proveedor_nit ?: '',
                        $row->proveedor_nrc ?: '',
                        number_format((float) $row->gravadas, 2, '.', ''),
                        number_format((float) $row->exentas, 2, '.', ''),
                        number_format((float) $row->no_sujetas, 2, '.', ''),
                        number_format((float) $row->iva, 2, '.', ''),
                        number_format((float) $row->total, 2, '.', ''),
                    ], ';');
                }
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
