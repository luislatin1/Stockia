<?php

namespace Stockia\DteSvMh\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Stockia\DteSvMh\Validators\ValidadorCoherenciaDte;
use Stockia\DteSvMh\Validators\ValidadorDocumentoSalvador;
use Symfony\Component\HttpFoundation\Response;

class ValidarDocumentoDte
{
    public function handle(Request $request, Closure $next): Response
    {
        $tipoDte = (string) ($request->input('tipo_dte') ?? '');
        if ($tipoDte === '') {
            return $next($request);
        }

        $tipoDocumento = (string) ($request->input('tipo_documento') ?? '');
        $numeroDocumento = (string) ($request->input('numero_documento') ?? '');

        $customerId = (int) ($request->input('customer_id') ?? 0);
        if ($customerId > 0 && ($tipoDocumento === '' || $numeroDocumento === '')) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $tipoDocumento = (string) $customer->tipo_documento;
                $numeroDocumento = (string) $customer->numero_documento;
            }
        }

        if ($tipoDocumento !== '' && $numeroDocumento !== '') {
            ValidadorDocumentoSalvador::validar($tipoDocumento, $numeroDocumento);
            ValidadorCoherenciaDte::validar($tipoDte, $tipoDocumento);
        }

        return $next($request);
    }
}
