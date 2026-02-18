<?php
namespace TuEmpresa\SvDte\Builders;

class FacturaBuilder
{
    public function make(array $input): array
    {
        return [
            "identificacion" => [
                "version" => 1,
                "ambiente" => config('dte.ambiente'),
                "tipoDte" => "01",
                "numeroControl" => $input['numero_control'],
                "codigoGeneracion" => $input['codigo_generacion'],
                "tipoModelo" => 1,
                "tipoOperacion" => 1,
                "tipoContingencia" => null,
                "motivoContin" => null,
                "fecEmi" => now()->format('Y-m-d'),
                "horEmi" => now()->format('H:i:s'),
                "tipoMoneda" => "USD"
            ],
            "emisor" => $input['emisor'],
            "receptor" => $input['receptor'],
            "cuerpoDocumento" => $input['items'],
            "resumen" => $input['totales'],
            "extension" => null,
            "apendice" => null
        ];
    }
}