<?php

namespace Database\Seeders;

use App\Models\ProductPrice;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prices = [
            // Ethiopian Light Roast (Product ID: 1)
            ['product_id' => 1, 'country_code' => 'MY', 'price' => 35.00],
            ['product_id' => 1, 'country_code' => 'SG', 'price' => 15.00],

            // Sumatra Dark Roast (Product ID: 2)
            ['product_id' => 2, 'country_code' => 'MY', 'price' => 40.00],
            ['product_id' => 2, 'country_code' => 'SG', 'price' => 17.00],

            // Colombian Medium Roast (Product ID: 3)
            ['product_id' => 3, 'country_code' => 'MY', 'price' => 38.00],
            ['product_id' => 3, 'country_code' => 'SG', 'price' => 16.50],

            // Brazilian Santos (Product ID: 4)
            ['product_id' => 4, 'country_code' => 'MY', 'price' => 32.00],
            ['product_id' => 4, 'country_code' => 'SG', 'price' => 14.00],

            // Guatemala Antigua (Product ID: 5)
            ['product_id' => 5, 'country_code' => 'MY', 'price' => 42.00],
            ['product_id' => 5, 'country_code' => 'SG', 'price' => 18.50],

            // Cold Brew Bottle (Product ID: 6)
            ['product_id' => 6, 'country_code' => 'MY', 'price' => 12.50],
            ['product_id' => 6, 'country_code' => 'SG', 'price' => 5.80],

            // Iced Latte (Product ID: 7)
            ['product_id' => 7, 'country_code' => 'MY', 'price' => 15.00],
            ['product_id' => 7, 'country_code' => 'SG', 'price' => 6.50],

            // Mocha Frappuccino (Product ID: 8)
            ['product_id' => 8, 'country_code' => 'MY', 'price' => 18.00],
            ['product_id' => 8, 'country_code' => 'SG', 'price' => 8.00],

            // Vanilla Cold Brew (Product ID: 9)
            ['product_id' => 9, 'country_code' => 'MY', 'price' => 14.00],
            ['product_id' => 9, 'country_code' => 'SG', 'price' => 6.20],

            // French Press (Product ID: 10)
            ['product_id' => 10, 'country_code' => 'MY', 'price' => 85.00],
            ['product_id' => 10, 'country_code' => 'SG', 'price' => 38.00],

            // Pour Over Dripper V60 (Product ID: 11)
            ['product_id' => 11, 'country_code' => 'MY', 'price' => 45.00],
            ['product_id' => 11, 'country_code' => 'SG', 'price' => 20.00],

            // Coffee Grinder Manual (Product ID: 12)
            ['product_id' => 12, 'country_code' => 'MY', 'price' => 120.00],
            ['product_id' => 12, 'country_code' => 'SG', 'price' => 55.00],

            // Espresso Maker Moka Pot (Product ID: 13)
            ['product_id' => 13, 'country_code' => 'MY', 'price' => 65.00],
            ['product_id' => 13, 'country_code' => 'SG', 'price' => 29.00],

            // Coffee Scale Digital (Product ID: 14)
            ['product_id' => 14, 'country_code' => 'MY', 'price' => 95.00],
            ['product_id' => 14, 'country_code' => 'SG', 'price' => 42.00],

            // Coffee Lover Starter Kit (Product ID: 15)
            ['product_id' => 15, 'country_code' => 'MY', 'price' => 180.00],
            ['product_id' => 15, 'country_code' => 'SG', 'price' => 80.00],

            // Premium Bean Sampler Set (Product ID: 16)
            ['product_id' => 16, 'country_code' => 'MY', 'price' => 150.00],
            ['product_id' => 16, 'country_code' => 'SG', 'price' => 68.00],

            // Cold Brew Discovery Pack (Product ID: 17)
            ['product_id' => 17, 'country_code' => 'MY', 'price' => 45.00],
            ['product_id' => 17, 'country_code' => 'SG', 'price' => 20.00],

            // Barista Essentials Bundle (Product ID: 18)
            ['product_id' => 18, 'country_code' => 'MY', 'price' => 250.00],
            ['product_id' => 18, 'country_code' => 'SG', 'price' => 115.00],
        ];

        foreach ($prices as $price) {
            ProductPrice::create($price);
        }
    }
}
