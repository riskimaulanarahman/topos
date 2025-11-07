<?php

namespace App\Support;

use App\Models\Outlet;
use App\Models\OutletUserRole;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class OutletContext
{
    protected static ?Outlet $currentOutlet = null;
    protected static ?OutletUserRole $currentRole = null;

    public static function setCurrent(?Outlet $outlet, ?OutletUserRole $role = null): void
    {
        static::$currentOutlet = $outlet;
        static::$currentRole = $role;
    }

    public static function currentOutlet(): ?Outlet
    {
        return static::$currentOutlet;
    }

    public static function currentRole(?int $outletId = null): ?OutletUserRole
    {
        if ($outletId && static::$currentRole && static::$currentRole->outlet_id !== $outletId) {
            static::$currentRole = null;
        }

        if (static::$currentRole) {
            return static::$currentRole;
        }

        $user = Auth::user();
        $outletId = $outletId ?: static::$currentOutlet?->id;

        if (! $user || ! $outletId) {
            return null;
        }

        return static::$currentRole = OutletUserRole::where('outlet_id', $outletId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
    }
}
