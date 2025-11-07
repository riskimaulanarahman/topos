<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Models\RawMaterial;
use App\Services\RecipeService;
use Illuminate\Http\Request;

class ProductRecipeWebController extends Controller
{
    public function edit(Product $product)
    {
        $recipe = ProductRecipe::with('items')->firstOrNew(['product_id' => $product->id]);
        $materials = RawMaterial::query()
            ->accessibleBy(auth()->user())
            ->where(function ($builder) use ($recipe) {
                $builder->where(function ($priced) {
                    $priced->where('unit_cost', '>', 0)
                        ->whereHas('expenseItems');
                });
                if ($recipe->exists) {
                    $builder->orWhereHas('recipeItems', function ($query) use ($recipe) {
                        $query->where('product_recipe_id', $recipe->id);
                    });
                }
            })
            ->orderBy('name')
            ->get();
        return view('pages.product_recipes.edit', compact('product','recipe','materials'));
    }

    public function update(Request $request, Product $product, RecipeService $recipes)
    {
        $data = $request->validate([
            'yield_qty' => ['required','numeric','min:0.0001'],
            'unit' => ['nullable','string','max:20'],
            'items' => ['required','array','min:1'],
            'items.*.raw_material_id' => ['required','exists:raw_materials,id'],
            'items.*.qty_per_yield' => ['required','numeric','min:0.0001'],
            'items.*.waste_pct' => ['nullable','numeric','min:0','max:100'],
        ]);

        $recipe = ProductRecipe::firstOrNew(['product_id' => $product->id]);
        $recipe->yield_qty = $data['yield_qty'];
        $recipe->unit = $data['unit'] ?? null;
        $recipe->save();

        $recipe->items()->delete();
        foreach ($data['items'] as $i) {
            ProductRecipeItem::create([
                'product_recipe_id' => $recipe->id,
                'raw_material_id' => $i['raw_material_id'],
                'qty_per_yield' => $i['qty_per_yield'],
                'waste_pct' => $i['waste_pct'] ?? 0,
            ]);
        }

        $product = $product->fresh();
        $product->cost_price = $recipes->calculateCogs($product);
        $estimate = $recipes->estimateBuildableUnits($product);
        $product->stock = max(0, (int) ($estimate ?? 0));
        $product->save();

        return redirect()->route('product-recipes.edit', $product->id)->with('success','Resep disimpan');
    }

}
