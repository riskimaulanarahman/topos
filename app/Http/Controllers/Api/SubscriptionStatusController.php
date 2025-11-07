<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $expiresAt = $user->subscription_expires_at;

        return response()->json([
            'subscription_status' => $user->subscription_status?->value,
            'subscription_expires_at' => $expiresAt?->toISOString(),
            'days_remaining' => $expiresAt ? now()->diffInDays($expiresAt, false) : null,
            'trial_started_at' => $user->trial_started_at?->toISOString(),
        ]);
    }
}
