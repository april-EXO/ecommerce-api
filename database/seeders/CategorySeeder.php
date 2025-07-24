<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Coffee Beans',
                'description' => 'Premium coffee beans from around the world'
            ],
            [
                'name' => 'Ready-to-Drink',
                'description' => 'Cold brew, iced coffee, and ready-to-drink beverages'
            ],
            [
                'name' => 'Coffee Equipment',
                'description' => 'Brewing equipment, grinders, and accessories'
            ],
            [
                'name' => 'Gift Sets',
                'description' => 'Curated coffee gift sets and bundles'
            ]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
