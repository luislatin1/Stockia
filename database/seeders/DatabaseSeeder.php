<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Validation\Rules\In;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            DteCatalogSeeder::class,
        ]);

        // Seeders de desarrollo — solo para entornos locales:
        // CompanySeeder::class
        // UserSeeder::class
        // InitialWarehouseSeeder::class
        // ModulesSeeder::class  ← usar solo si necesitas bypass del wizard de módulos
    }
}
