<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;

class UpdatedDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo users
        $user1 = User::firstOrCreate([
            'email' => 'admin@roarpos.com'
        ], [
            'name' => 'Admin User',
            'store_name' => 'Roar POS Demo Store',
            'email' => 'admin@roarpos.com',
            'password' => Hash::make('password'),
            'phone' => '081234567890',
            'roles' => 'admin',
            'email_verified_at' => now(),
        ]);

        $user2 = User::firstOrCreate([
            'email' => 'demo@roarpos.com'
        ], [
            'name' => 'Demo User',
            'store_name' => 'Demo Store',
            'email' => 'demo@roarpos.com',
            'password' => Hash::make('password'),
            'phone' => '081234567891',
            'roles' => 'user',
            'email_verified_at' => now(),
        ]);

        // Create categories for each user
        $categories1 = [
            ['name' => 'Beverages', 'user_id' => $user1->id],
            ['name' => 'Food', 'user_id' => $user1->id],
            ['name' => 'Snacks', 'user_id' => $user1->id],
        ];

        $categories2 = [
            ['name' => 'Coffee', 'user_id' => $user2->id],
            ['name' => 'Pastry', 'user_id' => $user2->id],
        ];

        foreach (array_merge($categories1, $categories2) as $categoryData) {
            Category::firstOrCreate([
                'name' => $categoryData['name'],
                'user_id' => $categoryData['user_id']
            ], [
                'name' => $categoryData['name'],
                'user_id' => $categoryData['user_id'],
                'sync_status' => 'synced',
                'version_id' => 1,
            ]);
        }

        // Create products for user1
        $beverageCategory = Category::where('name', 'Beverages')->where('user_id', $user1->id)->first();
        $foodCategory = Category::where('name', 'Food')->where('user_id', $user1->id)->first();
        $snacksCategory = Category::where('name', 'Snacks')->where('user_id', $user1->id)->first();

        if ($beverageCategory) {
            $products1 = [
                [
                    'name' => 'Ice Tea',
                    'description' => 'Fresh iced tea',
                    'price' => 15000,
                    'stock' => 100,
                    'category_id' => $beverageCategory->id,
                    'user_id' => $user1->id,
                ],
                [
                    'name' => 'Coffee',
                    'description' => 'Premium coffee',
                    'price' => 25000,
                    'stock' => 50,
                    'category_id' => $beverageCategory->id,
                    'user_id' => $user1->id,
                ],
            ];

            foreach ($products1 as $productData) {
                Product::firstOrCreate([
                    'name' => $productData['name'],
                    'user_id' => $productData['user_id']
                ], array_merge($productData, [
                    'sync_status' => 'synced',
                    'version_id' => 1,
                ]));
            }
        }

        if ($foodCategory) {
            $foodProducts = [
                [
                    'name' => 'Fried Rice',
                    'description' => 'Delicious fried rice',
                    'price' => 35000,
                    'stock' => 30,
                    'category_id' => $foodCategory->id,
                    'user_id' => $user1->id,
                ],
                [
                    'name' => 'Noodles',
                    'description' => 'Tasty noodles',
                    'price' => 30000,
                    'stock' => 25,
                    'category_id' => $foodCategory->id,
                    'user_id' => $user1->id,
                ],
            ];

            foreach ($foodProducts as $productData) {
                Product::firstOrCreate([
                    'name' => $productData['name'],
                    'user_id' => $productData['user_id']
                ], array_merge($productData, [
                    'sync_status' => 'synced',
                    'version_id' => 1,
                ]));
            }
        }

        // Create products for user2
        $coffeeCategory = Category::where('name', 'Coffee')->where('user_id', $user2->id)->first();
        
        if ($coffeeCategory) {
            $products2 = [
                [
                    'name' => 'Espresso',
                    'description' => 'Strong espresso',
                    'price' => 20000,
                    'stock' => 40,
                    'category_id' => $coffeeCategory->id,
                    'user_id' => $user2->id,
                ],
                [
                    'name' => 'Cappuccino',
                    'description' => 'Creamy cappuccino',
                    'price' => 28000,
                    'stock' => 35,
                    'category_id' => $coffeeCategory->id,
                    'user_id' => $user2->id,
                ],
            ];

            foreach ($products2 as $productData) {
                Product::firstOrCreate([
                    'name' => $productData['name'],
                    'user_id' => $productData['user_id']
                ], array_merge($productData, [
                    'sync_status' => 'synced',
                    'version_id' => 1,
                ]));
            }
        }

        $this->command->info('Demo data created successfully!');
        $this->command->info('Users created:');
        $this->command->info('- admin@roarpos.com (password: password)');
        $this->command->info('- demo@roarpos.com (password: password)');
    }
}
