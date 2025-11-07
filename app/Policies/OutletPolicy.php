<?php

namespace App\Policies;

use App\Models\Outlet;
use App\Models\User;

class OutletPolicy
{
    public function view(User $user, Outlet $outlet): bool
    {
        return $user->outletRoles()->where('outlet_id', $outlet->id)->where('status', 'active')->exists();
    }

    public function manageMembers(User $user, Outlet $outlet): bool
    {
        return $user->outletRoles()
            ->where('outlet_id', $outlet->id)
            ->where('role', 'owner')
            ->where('status', 'active')
            ->exists();
    }

    public function update(User $user, Outlet $outlet): bool
    {
        return $this->manageMembers($user, $outlet);
    }
}
