<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $currency = Currency::where('code', 'USD')->first();

        Company::updateOrCreate(
            ['name' => 'Stockia'],
            [
                'legal_name' => 'Stockia Demo S.A.',
                'tax_id'     => 'X00000000',
                'timezone'   => 'America/Mexico_City',
                'currency_id'=> $currency?->id,
            ]
        );
    }
}

