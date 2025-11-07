<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Models\RawMaterial;

class ProductRecipeSeeder extends Seeder
{
    public function run(): void
    {
        // Try to attach a recipe to an existing product (Coffee or Cappuccino)
        $product = Product::whereIn('name', ['Coffee','Cappuccino','Espresso'])->first();
        if (!$product) {
            return; // no product available
        }
        $recipe = ProductRecipe::firstOrCreate([
            'product_id' => $product->id,
        ], [
            'yield_qty' => 1,
            'unit' => 'pcs',
            'notes' => 'Default recipe',
        ]);

        // Ensure materials exist
        $sugar = RawMaterial::firstOrCreate(['sku' => 'SUGAR-001'], [
            'name' => 'Gula Pasir',
            'unit' => 'g',
            'unit_cost' => 0.0200,
            'stock_qty' => 50000,
            'min_stock' => 5000,
        ]);
        $milk = RawMaterial::firstOrCreate(['sku' => 'MILK-001'], [
            'name' => 'Susu',
            'unit' => 'ml',
            'unit_cost' => 0.0300,
            'stock_qty' => 20000,
            'min_stock' => 2000,
        ]);
        $beans = RawMaterial::firstOrCreate(['sku' => 'BEANS-001'], [
            'name' => 'Biji Kopi',
            'unit' => 'g',
            'unit_cost' => 0.1500,
            'stock_qty' => 10000,
            'min_stock' => 1000,
        ]);

        // Replace items
        $recipe->items()->delete();
        ProductRecipeItem::create([
            'product_recipe_id' => $recipe->id,
            'raw_material_id' => $beans->id,
            'qty_per_yield' => 10,
            'waste_pct' => 0,
        ]);
        ProductRecipeItem::create([
            'product_recipe_id' => $recipe->id,
            'raw_material_id' => $milk->id,
            'qty_per_yield' => 100,
            'waste_pct' => 5,
        ]);
        ProductRecipeItem::create([
            'product_recipe_id' => $recipe->id,
            'raw_material_id' => $sugar->id,
            'qty_per_yield' => 5,
            'waste_pct' => 0,
        ]);
    }
}

