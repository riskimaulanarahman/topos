<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SubscriptionManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        $users = User::query()
            ->when($search, function ($query, string $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('store_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('store_name')
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.subscriptions.index', [
            'users' => $users,
            'statuses' => SubscriptionStatus::cases(),
            'search' => $search,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'trial_started_at' => ['nullable', 'date'],
            'subscription_expires_at' => ['nullable', 'date', 'after_or_equal:trial_started_at'],
            'subscription_status' => ['required', 'in:'.implode(',', SubscriptionStatus::values())],
        ]);

        $trialStartedAt = isset($data['trial_started_at'])
            ? Carbon::parse($data['trial_started_at'])
            : now();

        $subscriptionExpiresAt = isset($data['subscription_expires_at'])
            ? Carbon::parse($data['subscription_expires_at'])
            : (clone $trialStartedAt)->addDays(14);

        $user->forceFill([
            'trial_started_at' => $trialStartedAt,
            'subscription_expires_at' => $subscriptionExpiresAt,
            'subscription_status' => $data['subscription_status'],
        ])->save();

        $redirectParams = array_filter($request->only('search', 'page'));

        return redirect()
            ->route('admin.subscriptions.index', $redirectParams)
            ->with('status', "Langganan untuk {$user->store_name} berhasil diperbarui.");
    }
}
