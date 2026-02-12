<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanyUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $company = Company::first();

        if ($user && $company) {
            $user->companies()->syncWithoutDetaching([
                $company->id
            ]);
        }
    }
}