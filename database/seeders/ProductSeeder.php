<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users and categories
        $users = User::all();
        $categories = Category::all();
        
        if ($users->isEmpty() || $categories->isEmpty()) {
            return; // Skip if no users or categories
        }
        
        $adminUser = $users->where('roles', 'admin')->first() ?? $users->first();
        $staffUser = $users->where('roles', 'staff')->first() ?? $users->last();
        
        // Sample products with proper structure
        $products = [
            [
                'name' => 'Nasi Goreng Spesial',
                'description' => 'Nasi goreng dengan telur dan ayam',
                'price' => 25000,
                'stock' => 50,
                'category_id' => $categories->first()->id,
                'user_id' => $adminUser->id,
            ],
            [
                'name' => 'Es Teh Manis',
                'description' => 'Teh manis dengan es batu',
                'price' => 5000,
                'stock' => 100,
                'category_id' => $categories->skip(1)->first()->id ?? $categories->first()->id,
                'user_id' => $adminUser->id,
            ],
            [
                'name' => 'Ayam Geprek',
                'description' => 'Ayam crispy dengan sambal geprek',
                'price' => 18000,
                'stock' => 30,
                'category_id' => $categories->first()->id,
                'user_id' => $staffUser->id,
            ],
            [
                'name' => 'Kopi Tubruk',
                'description' => 'Kopi hitam tradisional',
                'price' => 8000,
                'stock' => 75,
                'category_id' => $categories->skip(1)->first()->id ?? $categories->first()->id,
                'user_id' => $staffUser->id,
            ],
        ];
        
        // Add sync fields to each product
        foreach ($products as &$product) {
            $product['sync_status'] = 'synced';
            $product['last_synced'] = now();
            $product['client_version'] = 'v1.0.0';
            $product['version_id'] = 1;
            $product['created_at'] = now();
            $product['updated_at'] = now();
        }
        
        Product::insert($products);
    }
}
