<?php

namespace App\Exceptions;

/**
 * Error de negocio al crear una venta (cliente inválido, stock/producto
 * fuera de contexto, efectivo insuficiente, etc.) — distinto de un error de
 * validación de forma de Laravel porque depende de datos de BD, no solo del
 * shape del request. Cada controlador (web/API) decide cómo mostrarlo.
 */
class SaleValidationException extends \RuntimeException
{
}
