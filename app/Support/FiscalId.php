<?php

namespace App\Support;

/**
 * Identificadores fiscales salvadoreños: NIT, DUI y NRC.
 *
 * Criterio de diseño: en la base se guarda el valor "bonito" (con guiones, como lo
 * ve y lo imprime el usuario); el JSON del DTE exige SIN guiones (Normativa v2.0,
 * Anexo IV: NIT de 9 o 14 dígitos; DUI de 9 dígitos; NRC sin guion ni ceros a la
 * izquierda). Por eso no hay columna duplicada: la versión del DTE se deriva siempre
 * con estas funciones, de modo que nunca pueda desincronizarse.
 *
 * Nada aquí "arregla" datos mal formados: si un valor no es válido, las funciones
 * *Digits() devuelven null y quien emite debe negarse con un mensaje claro.
 */
class FiscalId
{
    /** Solo los dígitos de un valor (quita guiones, espacios, puntos, etc.). */
    public static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    // ---- NIT: 14 dígitos (persona jurídica/natural) o 9 (formato antiguo, igual al DUI) ----

    public static function nitDigits(?string $value): ?string
    {
        $d = self::digits($value);

        return in_array(strlen($d), [9, 14], true) && ! self::isAllZeros($d) ? $d : null;
    }

    public static function formatNit(?string $value): ?string
    {
        $d = self::nitDigits($value);
        if ($d === null) {
            return null;
        }

        return strlen($d) === 14
            ? substr($d, 0, 4).'-'.substr($d, 4, 6).'-'.substr($d, 10, 3).'-'.substr($d, 13, 1)
            : substr($d, 0, 8).'-'.substr($d, 8, 1);
    }

    // ---- DUI: 9 dígitos ----

    public static function duiDigits(?string $value): ?string
    {
        $d = self::digits($value);

        return strlen($d) === 9 && ! self::isAllZeros($d) ? $d : null;
    }

    public static function formatDui(?string $value): ?string
    {
        $d = self::duiDigits($value);

        return $d === null ? null : substr($d, 0, 8).'-'.substr($d, 8, 1);
    }

    /**
     * Dígito verificador del DUI (módulo 10, pesos 9..2). Informativo: Hacienda solo
     * valida "9 dígitos", así que no se usa para bloquear.
     */
    public static function duiCheckDigitOk(?string $value): bool
    {
        $d = self::duiDigits($value);
        if ($d === null) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += (int) $d[$i] * (9 - $i);
        }

        return (10 - ($sum % 10)) % 10 === (int) $d[8];
    }

    // ---- NRC: sin guion ni ceros a la izquierda; 2 a 8 caracteres ----

    public static function nrcDigits(?string $value): ?string
    {
        $d = ltrim(self::digits($value), '0');

        return strlen($d) >= 2 && strlen($d) <= 8 ? $d : null;
    }

    public static function formatNrc(?string $value): ?string
    {
        $d = self::nrcDigits($value);

        return $d === null ? null : substr($d, 0, -1).'-'.substr($d, -1);
    }

    // ---- Según tipo de documento (CAT-022) ----

    /**
     * Valor del número de documento tal como va en el JSON del DTE.
     * 36 = NIT, 13 = DUI. Otros tipos (pasaporte, carné de residente, otros) se
     * envían tal cual, recortados: no tienen formato fijo.
     * Devuelve null si el tipo es NIT/DUI y el valor no es válido.
     */
    public static function documentoParaDte(?string $tipoDocumento, ?string $numero): ?string
    {
        return match ((string) $tipoDocumento) {
            '36' => self::nitDigits($numero),
            '13' => self::duiDigits($numero),
            default => ($t = trim((string) $numero)) === '' ? null : $t,
        };
    }

    /**
     * Valor para guardar en la base: formato con guiones si el dato es válido; si no,
     * se deja tal cual (recortado). Nunca inventa ni descarta información.
     */
    public static function normalizarDocumento(?string $tipoDocumento, ?string $numero): ?string
    {
        if ($numero === null) {
            return null;
        }

        $formateado = match ((string) $tipoDocumento) {
            '36' => self::formatNit($numero),
            '13' => self::formatDui($numero),
            default => null,
        };

        return $formateado ?? trim($numero);
    }

    public static function normalizarNit(?string $numero): ?string
    {
        return $numero === null ? null : (self::formatNit($numero) ?? trim($numero));
    }

    public static function normalizarDui(?string $numero): ?string
    {
        return $numero === null ? null : (self::formatDui($numero) ?? trim($numero));
    }

    public static function normalizarNrc(?string $numero): ?string
    {
        return $numero === null ? null : (self::formatNrc($numero) ?? trim($numero));
    }

    private static function isAllZeros(string $digits): bool
    {
        return trim($digits, '0') === '';
    }
}
