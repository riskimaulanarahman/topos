<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Outlet;
use App\Models\OutletUserRole;
use App\Scopes\OutletScope;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ResolvesOutlet
{
    protected function resolveOutletId(Request $request, bool $require = false): ?int
    {
        $outletId = $this->resolveAccessibleOutletId($request, $require);

        if ($outletId) {
            $this->activateOutletContext($request->user(), $outletId);
        }

        return $outletId;
    }

    protected function resolveAccessibleOutletId(Request $request, bool $require = false): ?int
    {
        $user = $request->user();
        if (! $user) {
            if ($require) {
                throw ValidationException::withMessages([
                    'outlet_id' => __('User tidak terautentikasi.'),
                ]);
            }

            return null;
        }

        $candidate = $request->input('outlet_id');
        if ($candidate) {
            $candidate = (int) $candidate;
        }

        if (! $candidate) {
            $candidate = OutletContext::currentOutlet()?->id;
        }

        if (! $candidate) {
            $candidate = $user->outletRoles()
                ->where('status', 'active')
                ->orderByRaw("role = 'owner' DESC")
                ->orderBy('created_at')
                ->value('outlet_id');
        }

        if (! $candidate) {
            $candidate = $this->provisionDefaultOutlet($user);
        }

        if (! $candidate) {
            if ($require) {
                throw ValidationException::withMessages([
                    'outlet_id' => __('Outlet belum tersedia untuk pengguna ini.'),
                ]);
            }

            return null;
        }

        $this->assertUserHasOutletAccess($user, $candidate);

        return (int) $candidate;
    }

    protected function assertUserHasOutletAccess($user, int $outletId): void
    {
        $hasAccess = $user->outletRoles()
            ->where('outlet_id', $outletId)
            ->where('status', 'active')
            ->exists();

        if (! $hasAccess) {
            abort(403, __('Anda tidak memiliki akses ke outlet tersebut.'));
        }
    }

    protected function provisionDefaultOutlet($user): ?int
    {
        if (! $user) {
            return null;
        }

        $existing = $user->outletRoles()
            ->where('status', 'active')
            ->orderByRaw("role = 'owner' DESC")
            ->orderBy('created_at')
            ->value('outlet_id');

        if ($existing) {
            return (int) $existing;
        }

        $outletName = $user->store_name ?: ($user->name ?: 'Outlet ' . $user->id);

        $outlet = Outlet::create([
            'name' => $outletName,
            'created_by' => $user->id,
        ]);

        OutletUserRole::create([
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
            'can_manage_stock' => true,
            'can_manage_expense' => true,
            'can_manage_sales' => true,
            'accepted_at' => now(),
            'created_by' => $user->id,
        ]);

        return $outlet->id;
    }

    protected function activateOutletContext($user, int $outletId): void
    {
        OutletScope::setActiveOutletId($outletId);

        $outlet = Outlet::find($outletId);
        $role = $user?->outletRoles()
            ->where('outlet_id', $outletId)
            ->where('status', 'active')
            ->first();

        OutletContext::setCurrent($outlet, $role);
    }
}
