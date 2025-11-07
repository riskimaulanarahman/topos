<?php

namespace App\Http\Middleware;

use App\Enums\SubscriptionStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (!$user->trial_started_at || !$user->subscription_expires_at || !$user->subscription_status) {
            $trialStartedAt = $user->trial_started_at ?? now();
            $user->forceFill([
                'trial_started_at' => $trialStartedAt,
                'subscription_expires_at' => $trialStartedAt->copy()->addDays(7),
                'subscription_status' => SubscriptionStatus::TRIALING,
            ])->save();
        }

        if ($request->routeIs([
            'billing.*',
            'logout',
            'admin.subscriptions.*',
        ])) {
            return $next($request);
        }

        $status = $user->subscription_status;

        if (in_array($status, [SubscriptionStatus::TRIALING, SubscriptionStatus::ACTIVE], true)) {
            $expiresAt = $user->subscription_expires_at;
            if ($expiresAt && $expiresAt->isPast()) {
                $user->forceFill([
                    'subscription_status' => SubscriptionStatus::EXPIRED,
                ])->save();
                $status = $user->subscription_status;
            }
        }

        if (in_array($status, [SubscriptionStatus::TRIALING, SubscriptionStatus::ACTIVE], true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subscription is not active. Please renew to continue.',
                'subscription_status' => $status?->value,
                'subscription_expires_at' => optional($user->subscription_expires_at)->toISOString(),
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        return redirect()->route('billing.index')->with('subscription_expired', true);
    }
}
