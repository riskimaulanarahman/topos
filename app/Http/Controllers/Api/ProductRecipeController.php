<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRecipeStoreRequest;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Services\RecipeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProductRecipeController extends Controller
{
    public function __construct(private RecipeService $recipes)
    {
    }

    public function showRecipe(int $id)
    {
        Gate::authorize('inventory.manage');
        $recipe = ProductRecipe::with('items.rawMaterial')->where('product_id', $id)->first();
        return response()->json(['data' => $recipe]);
    }

    public function storeRecipe(ProductRecipeStoreRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $data = $request->validated();

        $recipe = DB::transaction(function () use ($id, $data) {
            // Upsert recipe
            $recipe = ProductRecipe::firstOrNew(['product_id' => $id]);
            $recipe->yield_qty = $data['yield_qty'];
            $recipe->unit = $data['unit'] ?? null;
            $recipe->notes = $data['notes'] ?? null;
            $recipe->save();

            // Replace items
            $recipe->items()->delete();
            foreach ($data['items'] as $item) {
                ProductRecipeItem::create([
                    'product_recipe_id' => $recipe->id,
                    'raw_material_id' => $item['raw_material_id'],
                    'qty_per_yield' => $item['qty_per_yield'],
                    'waste_pct' => $item['waste_pct'] ?? 0,
                ]);
            }
            $recipe = $recipe->load('items.rawMaterial');

            // Update HPP (cost_price) pada products dari resep
            $product = Product::findOrFail($id);
            $product->cost_price = $this->recipes->calculateCogs($product);
            $estimate = $this->recipes->estimateBuildableUnits($product);
            $product->stock = max(0, (int) ($estimate ?? 0));
            $product->save();

            return $recipe;
        });

        return response()->json(['message' => 'Recipe saved', 'data' => $recipe]);
    }

    public function cogs(int $id)
    {
        Gate::authorize('inventory.manage');
        $product = Product::findOrFail($id);
        $cogs = $this->recipes->calculateCogs($product);
        return response()->json(['data' => ['cogs_per_unit' => $cogs]]);
    }
}
