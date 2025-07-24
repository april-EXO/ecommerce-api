<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs
        $coffeeBeansCategoryId = Category::where('name', 'Coffee Beans')->first()->id;
        $readyToDrinkCategoryId = Category::where('name', 'Ready-to-Drink')->first()->id;
        $equipmentCategoryId = Category::where('name', 'Coffee Equipment')->first()->id;
        $giftSetsCategoryId = Category::where('name', 'Gift Sets')->first()->id;

        $products = [
            // Coffee Beans Category
            [
                'name' => 'Ethiopian Light Roast',
                'description' => 'Bright and fruity single-origin beans.',
                'image_url' => 'Ethiopian Light Roast.jpg',
                'category_id' => $coffeeBeansCategoryId,
            ],
            [
                'name' => 'Sumatra Dark Roast',
                'description' => 'Bold and earthy coffee with low acidity.',
                'image_url' => 'Sumatra Dark Roast.jpg',
                'category_id' => $coffeeBeansCategoryId,
            ],
            [
                'name' => 'Colombian Medium Roast',
                'description' => 'Well-balanced with notes of caramel and chocolate.',
                'image_url' => 'Colombian Medium Roast.jpg',
                'category_id' => $coffeeBeansCategoryId,
            ],
            [
                'name' => 'Brazilian Santos',
                'description' => 'Classic Brazilian coffee with nutty undertones.',
                'image_url' => 'Brazilian Santos.jpg',
                'category_id' => $coffeeBeansCategoryId,
            ],
            [
                'name' => 'Guatemala Antigua',
                'description' => 'Full-bodied with spicy and smoky flavors.',
                'image_url' => 'Guatemala Antigua.jpg',
                'category_id' => $coffeeBeansCategoryId,
            ],

            // Ready-to-Drink Category
            [
                'name' => 'Cold Brew Bottle (250ml)',
                'description' => 'Ready-to-drink cold brew, smooth and refreshing.',
                'image_url' => 'Cold Brew Bottle (250ml).jpg',
                'category_id' => $readyToDrinkCategoryId,
            ],
            [
                'name' => 'Iced Latte (300ml)',
                'description' => 'Creamy iced latte with premium espresso.',
                'image_url' => 'Iced Latte (300ml).jpg',
                'category_id' => $readyToDrinkCategoryId,
            ],
            [
                'name' => 'Mocha Frappuccino (350ml)',
                'description' => 'Rich chocolate and coffee blended drink.',
                'image_url' => 'Mocha Frappuccino (350ml).jpg',
                'category_id' => $readyToDrinkCategoryId,
            ],
            [
                'name' => 'Vanilla Cold Brew (250ml)',
                'description' => 'Cold brew with a hint of vanilla sweetness.',
                'image_url' => 'Vanilla Cold Brew (250ml).jpg',
                'category_id' => $readyToDrinkCategoryId,
            ],

            // Coffee Equipment Category
            [
                'name' => 'French Press (600ml)',
                'description' => 'Classic glass French press for brewing coffee.',
                'image_url' => 'French Press (600ml).jpg',
                'category_id' => $equipmentCategoryId,
            ],
            [
                'name' => 'Pour Over Dripper V60',
                'description' => 'Ceramic V60 dripper for precision brewing.',
                'image_url' => 'Pour Over Dripper V60.jpg',
                'category_id' => $equipmentCategoryId,
            ],
            [
                'name' => 'Coffee Grinder Manual',
                'description' => 'Hand-crank burr grinder for fresh grounds.',
                'image_url' => 'Coffee Grinder Manual.jpg',
                'category_id' => $equipmentCategoryId,
            ],
            [
                'name' => 'Espresso Maker Moka Pot',
                'description' => 'Traditional Italian stovetop espresso maker.',
                'image_url' => 'Espresso Maker Moka Pot.jpg',
                'category_id' => $equipmentCategoryId,
            ],
            [
                'name' => 'Coffee Scale Digital',
                'description' => 'Precision scale with timer for brewing.',
                'image_url' => 'Coffee Scale Digital.jpg',
                'category_id' => $equipmentCategoryId,
            ],

            // Gift Sets Category
            [
                'name' => 'Coffee Lover Starter Kit',
                'description' => 'Complete set with beans, French press, and grinder.',
                'image_url' => 'Coffee Lover Starter Kit.jpg',
                'category_id' => $giftSetsCategoryId,
            ],
            [
                'name' => 'Premium Bean Sampler Set',
                'description' => 'Collection of 5 different origin coffee beans.',
                'image_url' => 'Premium Bean Sampler Set.jpg',
                'category_id' => $giftSetsCategoryId,
            ],
            [
                'name' => 'Cold Brew Discovery Pack',
                'description' => 'Variety pack of ready-to-drink cold brews.',
                'image_url' => 'Cold Brew Discovery Pack.jpg',
                'category_id' => $giftSetsCategoryId,
            ],
            [
                'name' => 'Barista Essentials Bundle',
                'description' => 'Professional tools for home baristas.',
                'image_url' => 'Barista Essentials Bundle.jpg',
                'category_id' => $giftSetsCategoryId,
            ],
        ];

        foreach ($products as $data) {
            Product::create($data);
        }
    }
}
