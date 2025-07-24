<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['code' => 'MY', 'name' => 'Malaysia', 'currency_code' => 'MYR'],
            ['code' => 'SG', 'name' => 'Singapore', 'currency_code' => 'SGD'],
        ];

        foreach ($countries as $data) {
            Country::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
