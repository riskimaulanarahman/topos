<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;

class RecipeService
{
    /**
     * Calculate COGS for a product based on recipe and current average cost.
     */
    public function calculateCogs(Product $product): float
    {
        $recipe = ProductRecipe::with(['items.rawMaterial'])->where('product_id', $product->id)->first();
        if (!$recipe) {
            return 0.0;
        }
        $totalCostPerYield = 0.0;
        foreach ($recipe->items as $item) {
            /** @var RawMaterial $material */
            $material = $item->rawMaterial;
            $qtyNeeded = (float) $item->qty_per_yield * (1 + ((float)$item->waste_pct / 100));
            $totalCostPerYield += $qtyNeeded * (float) $material->unit_cost;
        }

        $yield = max(1e-9, (float) $recipe->yield_qty);
        $cogs = $totalCostPerYield / $yield;
        return round($cogs, 4);
    }

    public function estimateBuildableUnits(Product $product): ?int
    {
        $recipe = ProductRecipe::with(['items.rawMaterial'])->where('product_id', $product->id)->first();
        if (! $recipe || $recipe->items->isEmpty()) {
            return null;
        }

        $yield = max(1e-9, (float) $recipe->yield_qty);
        $possibleUnits = null;

        foreach ($recipe->items as $item) {
            /** @var RawMaterial|null $material */
            $material = $item->rawMaterial;
            if (! $material) {
                return 0;
            }

            $qtyPerUnit = (float) $item->qty_per_yield * (1 + ((float) $item->waste_pct / 100)) / $yield;
            if ($qtyPerUnit <= 0) {
                continue;
            }

            $available = (float) $material->stock_qty;
            $unitsFromMaterial = (int) floor($available / $qtyPerUnit);
            $possibleUnits = is_null($possibleUnits)
                ? $unitsFromMaterial
                : min($possibleUnits, $unitsFromMaterial);
        }

        return $possibleUnits ?? null;
    }

}
