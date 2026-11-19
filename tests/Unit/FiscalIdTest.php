<?php

namespace Tests\Unit;

use App\Support\FiscalId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FiscalIdTest extends TestCase
{
    public function test_nit_de_14_digitos_se_deriva_sin_guiones_y_se_formatea_con_guiones(): void
    {
        // Dato real de la empresa en la base.
        $this->assertSame('05122808871013', FiscalId::nitDigits('0512-280887-101-3'));
        $this->assertSame('05122808871013', FiscalId::nitDigits('05122808871013'));
        $this->assertSame('0512-280887-101-3', FiscalId::formatNit('05122808871013'));
        $this->assertSame('0512-280887-101-3', FiscalId::formatNit('0512-280887-101-3'));
    }

    public function test_nit_antiguo_de_9_digitos_es_valido_y_se_formatea_como_dui(): void
    {
        $this->assertSame('012345678', FiscalId::nitDigits('01234567-8'));
        $this->assertSame('01234567-8', FiscalId::formatNit('012345678'));
    }

    #[DataProvider('nitsInvalidos')]
    public function test_nit_mal_formado_no_se_acepta_ni_se_inventa(string $valor): void
    {
        $this->assertNull(FiscalId::nitDigits($valor));
        $this->assertNull(FiscalId::formatNit($valor));
    }

    public static function nitsInvalidos(): array
    {
        // Todos son valores reales encontrados en proveedores de la base.
        return [
            'faltan dígitos (13)' => ['0614-008011-03-7'],
            'ceros de relleno' => ['0000-000000-00-0'],
            'otro relleno' => ['0614-000000-00-0'],
            'basura 1' => ['0---6'],
            'basura 2' => ['---0'],
            'vacío' => [''],
        ];
    }

    public function test_dui_son_9_digitos_con_o_sin_guion(): void
    {
        $this->assertSame('012345678', FiscalId::duiDigits('01234567-8'));
        $this->assertSame('012345678', FiscalId::duiDigits('012345678'));
        $this->assertSame('01234567-8', FiscalId::formatDui('012345678'));
        $this->assertNull(FiscalId::duiDigits('00000000-0'));   // el DUI "de relleno" no es válido
        $this->assertNull(FiscalId::duiDigits('1234567-8'));    // 8 dígitos
    }

    public function test_digito_verificador_del_dui_es_informativo(): void
    {
        $this->assertTrue(FiscalId::duiCheckDigitOk('01234567-8'));   // 112 -> (10-2)%10 = 8
        $this->assertFalse(FiscalId::duiCheckDigitOk('01234567-9'));
        $this->assertFalse(FiscalId::duiCheckDigitOk('abc'));
    }

    public function test_nrc_sin_guion_ni_ceros_a_la_izquierda(): void
    {
        $this->assertSame('231194', FiscalId::nrcDigits('23119-4'));     // dato real de la empresa
        $this->assertSame('1234', FiscalId::nrcDigits('000123-4'));
        $this->assertSame('23119-4', FiscalId::formatNrc('231194'));
        $this->assertNull(FiscalId::nrcDigits('00000000'));
        $this->assertNull(FiscalId::nrcDigits('-1'));
        $this->assertNull(FiscalId::nrcDigits('5'));                     // mínimo 2
        $this->assertNull(FiscalId::nrcDigits('123456789'));             // máximo 8
    }

    public function test_documento_para_el_dte_segun_tipo(): void
    {
        $this->assertSame('05122808871013', FiscalId::documentoParaDte('36', '0512-280887-101-3'));
        $this->assertSame('012345678', FiscalId::documentoParaDte('13', '01234567-8'));
        $this->assertSame('AB 1234', FiscalId::documentoParaDte('03', '  AB 1234 '));  // pasaporte: sin formato fijo
        $this->assertNull(FiscalId::documentoParaDte('13', '00000000-0'));
        $this->assertNull(FiscalId::documentoParaDte('36', '0---6'));
        $this->assertNull(FiscalId::documentoParaDte('03', '   '));
    }

    public function test_normalizar_para_guardar_formatea_lo_valido_y_no_toca_lo_dudoso(): void
    {
        $this->assertSame('0512-280887-101-3', FiscalId::normalizarDocumento('36', '05122808871013'));
        $this->assertSame('01234567-8', FiscalId::normalizarDocumento('13', '012345678'));
        $this->assertSame('AB123', FiscalId::normalizarDocumento('03', ' AB123 '));
        // Lo dudoso se conserva tal cual (solo recortado): no se inventa ni se pierde información.
        $this->assertSame('0---6', FiscalId::normalizarNit('0---6'));
        $this->assertSame('0614-008011-03-7', FiscalId::normalizarNit('0614-008011-03-7'));
        $this->assertSame('-1', FiscalId::normalizarNrc('-1'));
        $this->assertNull(FiscalId::normalizarNit(null));
        $this->assertSame('23119-4', FiscalId::normalizarNrc('231194'));
    }
}
