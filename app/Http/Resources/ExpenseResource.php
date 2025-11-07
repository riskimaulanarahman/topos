<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            'reference_no' => $this->reference_no,
            'amount' => (float) $this->amount,
            'category' => $this->whenLoaded('category'),
            'vendor' => $this->vendor,
            'notes' => $this->notes,
            'attachment_url' => $this->attachment_path ? Storage::url($this->attachment_path) : null,
            'items' => ExpenseItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
