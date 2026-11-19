<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1) Numeración del número de control según Normativa 7.1.2:
 *    un solo consecutivo por empresa (centralizada) o por establecimiento,
 *    nunca por punto de venta ni por tipo de documento, reiniciado en cada
 *    ejercicio impositivo. Reemplaza a `dte_correlativos` (por tipo+estab+PV),
 *    que se conserva sin uso para no perder historial en instalaciones que
 *    ya tengan filas.
 *
 * 2) Secretos: cifra `api_password` (antes en texto plano) y agrega
 *    `signer_password` (contraseña de la llave privada, que el firmador oficial
 *    pide en cada firma). El certificado vive en el firmador de cada cliente,
 *    no en Stockia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dte_numeraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('scope_key', 20);           // 'C' (centralizada) o 'E<id>' (por establecimiento)
            $table->unsignedSmallInteger('ejercicio'); // año del ejercicio impositivo
            $table->unsignedBigInteger('ultimo')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'scope_key', 'ejercicio'], 'dte_numeraciones_unique');
        });

        Schema::table('dte_settings', function (Blueprint $table) {
            $table->string('numeracion_modo', 20)->default('centralizada')->after('punto_venta');
            $table->text('signer_password')->nullable()->after('signer_url');
        });

        // api_password pasa a ser cifrado: no puede quedar texto plano en la BD.
        DB::table('dte_settings')->whereNotNull('api_password')->orderBy('id')->each(function ($row) {
            if ($row->api_password === '' || $this->isEncrypted($row->api_password)) {
                return;
            }

            DB::table('dte_settings')->where('id', $row->id)
                ->update(['api_password' => Crypt::encryptString($row->api_password)]);
        });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }

    public function down(): void
    {
        // Se deja api_password cifrado: descifrarlo de vuelta a texto plano
        // reintroduciría el problema que esta migración corrige.
        Schema::table('dte_settings', function (Blueprint $table) {
            $table->dropColumn(['numeracion_modo', 'signer_password']);
        });

        Schema::dropIfExists('dte_numeraciones');
    }
};
