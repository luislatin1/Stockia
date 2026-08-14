<?php

namespace Stockia\DteSvMh\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;

class DteCorrelative extends Model
{
    protected $table = 'dte_correlativos';

    protected $fillable = [
        'company_id',
        'tipo_dte',
        'establecimiento',
        'punto_venta',
        'correlativo_actual',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
