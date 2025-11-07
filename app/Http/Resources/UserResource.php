<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_name' => $this->store_name,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'roles' => $this->roles,
            'store_description' => $this->store_description,
            'store_logo_url' => $this->store_logo_url,
            'operating_hours' => $this->operating_hours ?? [],
            'store_addresses' => $this->store_addresses ?? [],
            'map_links' => $this->map_links ?? [],
            'subscription_status' => $this->subscription_status?->value,
            'subscription_expires_at' => optional($this->subscription_expires_at)?->toISOString(),
            'trial_started_at' => optional($this->trial_started_at)?->toISOString(),
            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
    }
}

