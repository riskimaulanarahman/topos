<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RawMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'unit' => $this->unit,
            'unit_cost' => round((float) $this->unit_cost, 1),
            'stock_qty' => round((float) $this->stock_qty, 1),
            'min_stock' => round((float) $this->min_stock, 1),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'category_ids' => $this->whenLoaded('categories', fn () => $this->categories->pluck('id')->values()),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])),
        ];
    }
}
