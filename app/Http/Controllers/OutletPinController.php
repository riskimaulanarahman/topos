<?php

namespace App\Http\Controllers;

use App\Http\Requests\Outlet\UpdateOutletPinRequest;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;

class OutletPinController extends Controller
{
    public function update(UpdateOutletPinRequest $request, Outlet $outlet): RedirectResponse
    {
        $this->authorize('view', $outlet);

        $user = $request->user();

        $member = $user->outletRoles()
            ->where('outlet_id', $outlet->id)
            ->where('status', 'active')
            ->firstOrFail();

        $member->setPin($request->input('pin'));
        $member->save();

        return back()->with('success', __('PIN outlet berhasil diperbarui.'));
    }
}

