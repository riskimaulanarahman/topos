<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'raw_material_id' => $this->raw_material_id,
            'raw_material' => $this->whenLoaded('rawMaterial'),
            'description' => $this->description,
            'unit' => $this->unit,
            'qty' => (float) $this->qty,
            'unit_cost' => round((float) $this->unit_cost, 1),
            'item_price' => (float) $this->total_cost,
            'total_cost' => (float) $this->total_cost,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
