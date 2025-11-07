<?php

namespace Tests\Feature;

use App\Models\RawMaterial;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Services\InventoryService;
use App\Services\RecipeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeAndInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_average_cost_and_production_consumption(): void
    {
        $material = RawMaterial::create([
            'sku' => 'MAT-001',
            'name' => 'Material',
            'unit' => 'g',
            'unit_cost' => 0.0,
            'stock_qty' => 0,
            'min_stock' => 0,
        ]);

        $inv = new InventoryService();
        // Purchase 1000g @ 0.0100
        $inv->adjustStock($material, 1000, 'purchase', 0.0100, 'test', 1, 'init');
        $this->assertEquals(1000.0, (float)$material->fresh()->stock_qty);
        $this->assertEquals(0.0100, (float)$material->fresh()->unit_cost);
        // Purchase 1000g @ 0.0300 => new avg should be 0.0200
        $inv->adjustStock($material->fresh(), 1000, 'purchase', 0.0300, 'test', 2, 'second');
        $this->assertEquals(2000.0, (float)$material->fresh()->stock_qty);
        $this->assertEquals(0.0200, round((float)$material->fresh()->unit_cost, 4));

        $product = Product::create([
            'name' => 'Prod',
            'price' => 1000,
            'stock' => 0,
        ]);
        $recipe = ProductRecipe::create([
            'product_id' => $product->id,
            'yield_qty' => 1,
            'unit' => 'pcs',
        ]);
        ProductRecipeItem::create([
            'product_recipe_id' => $recipe->id,
            'raw_material_id' => $material->id,
            'qty_per_yield' => 10,
            'waste_pct' => 10,
        ]);

        $recipes = new RecipeService();
        $cogs = $recipes->calculateCogs($product);
        // qty needed = 10 * 1.1 = 11g; avg cost 0.0200 => 0.22
        $this->assertEquals(0.22, round($cogs, 2));

        $estimatedUnits = $recipes->estimateBuildableUnits($product->fresh());
        $this->assertEquals((int) floor(2000 / 11), $estimatedUnits);

    }
}

