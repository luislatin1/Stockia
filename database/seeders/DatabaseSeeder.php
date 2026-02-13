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
    CompanySeeder::class,
    UserSeeder::class,
    //CompanyUserSeeder::class,
    InitialWarehouseSeeder::class,
]);
    }
}
