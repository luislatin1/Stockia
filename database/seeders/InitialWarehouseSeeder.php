<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class InitialWarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {

            $warehouse = Warehouse::create([
                'company_id' => $company->id,
                'name' => 'Principal',
                'code' => 'MAIN-'.$company->id,
            ]);

            $products = Product::where('company_id', $company->id)->get();

            foreach ($products as $product) {
                DB::table('product_warehouse')->insert([
                    'company_id' => $company->id,
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'stock' => $product->stock ?? 0,
                    'min_stock' => $product->min_stock ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
