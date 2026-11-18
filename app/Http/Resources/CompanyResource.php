<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'nit' => $this->nit,
            'nrc' => $this->nrc,
            'estado' => $this->estado,
            'currency_id' => $this->currency_id,
            'timezone' => $this->timezone,
        ];
    }
}
