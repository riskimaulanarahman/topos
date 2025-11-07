<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use InvalidArgumentException;

class SubscriptionRenewalService
{
    public function extendByPlan(User $user, array $plan): User
    {
        $duration = $plan['duration'] ?? null;
        if (! $duration) {
            throw new InvalidArgumentException('Durasi paket tidak ditemukan.');
        }

        $interval = $this->makeInterval($duration);

        $startDate = $user->subscription_expires_at instanceof Carbon && $user->subscription_expires_at->isFuture()
            ? $user->subscription_expires_at->copy()
            : now();

        $newExpiry = $startDate->copy()->add($interval);
        $trialStartedAt = $user->trial_started_at ?? now();

        $user->forceFill([
            'trial_started_at' => $trialStartedAt,
            'subscription_status' => SubscriptionStatus::ACTIVE,
            'subscription_expires_at' => $newExpiry,
        ])->save();

        return $user->refresh();
    }

    protected function makeInterval(string $duration): CarbonInterval
    {
        try {
            $interval = CarbonInterval::make($duration);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('Format durasi paket tidak valid.');
        }

        if (! $interval) {
            throw new InvalidArgumentException('Durasi paket tidak dapat diproses.');
        }

        return $interval;
    }
}

