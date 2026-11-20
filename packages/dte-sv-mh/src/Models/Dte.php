<?php

namespace Stockia\DteSvMh\Models;

use App\Models\Company;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;

class Dte extends Model
{
    protected $table = 'dtes';

    protected $fillable = [
        'company_id',
        'sale_id',
        'purchase_id',
        'dte_origen_id',
        'sale_adjustment_id',
        'tipo_dte',
        'codigo_generacion',
        'numero_control',
        'json_original',
        'json_firmado',
        'sello_recepcion',
        'respuesta_hacienda',
        'estado',
        'fecha_envio',
    ];

    protected $casts = [
        'json_original' => 'array',
        'json_firmado' => 'array',
        'respuesta_hacienda' => 'array',
        'fecha_envio' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Archivo DTE que se entrega al receptor (Normativa Anexo I 4.1 y Manual Técnico §6.5): la estructura de datos
     * íntegra más `firmaElectronica` (JWS) y `selloRecibido`. Nombre de archivo: nombreArchivo().
     */
    public function archivoDte(): array
    {
        $firmado = is_array($this->json_firmado) ? $this->json_firmado : [];

        return ($this->json_original ?? []) + [
            'firmaElectronica' => $firmado['jsonFirmado'] ?? null,
            'selloRecibido' => $this->sello_recepcion,
        ];
    }

    public function nombreArchivo(): string
    {
        return $this->codigo_generacion.'.json';
    }
}
