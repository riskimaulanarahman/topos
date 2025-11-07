<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SubscriptionPlanService
{
    public function eligibleForFirstRenewalPromo(User $user): bool
    {
        return $user->subscription_status?->value === 'trialing';
    }

    public function getPlansForUser(User $user): Collection
    {
        $eligiblePromo = $this->eligibleForFirstRenewalPromo($user);
        return collect(config('subscriptions.plans', []))
            ->map(function (array $plan, string $key) use ($eligiblePromo) {
                $plan['code'] = $plan['code'] ?? $key;
                $normalPrice = (int) ($plan['price'] ?? 0);
                $plan['normal_price'] = $normalPrice;
                $plan['promo_active'] = false;
                if ($eligiblePromo && isset($plan['promo_price'])) {
                    $plan['price'] = (int) $plan['promo_price'];
                    $plan['promo_active'] = true;
                } else {
                    $plan['price'] = $normalPrice;
                }

                return $plan;
            })
            ->keyBy('code');
    }

    public function findPlanOrFail(User $user, string $planCode): array
    {
        $plans = $this->getPlansForUser($user);
        $plan = $plans->get($planCode);

        if (! $plan) {
            throw ValidationException::withMessages([
                'plan_code' => 'Paket langganan tidak ditemukan atau tidak tersedia.',
            ]);
        }

        return $plan;
    }
}

