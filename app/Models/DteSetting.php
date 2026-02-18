<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DteSetting extends Model
{
    protected $table = 'dte_settings';

    protected $fillable = [
        'company_id',
        'enabled',
        'integration_mode',
        'ambiente',
        'establecimiento',
        'punto_venta',
        'api_user',
        'api_password',
        'auth_url',
        'send_url',
        'signer_url',
        'use_dummy_certificate',
        'dummy_certificate_text',
        'static_token',
        'static_sello',
        'static_estado',
        'static_response',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'use_dummy_certificate' => 'boolean',
        'static_response' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function normalizedMode(): string
    {
        $mode = strtolower(trim((string) ($this->integration_mode ?: config('dte.mode', 'simulacion'))));
        if ($mode === 'static') {
            return 'simulacion';
        }

        return in_array($mode, ['simulacion', 'real', 'contingencia'], true) ? $mode : 'simulacion';
    }
}
