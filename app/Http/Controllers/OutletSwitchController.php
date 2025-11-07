<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Scopes\OutletScope;
use App\Support\OutletContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OutletSwitchController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'outlet_id' => ['required', 'integer', 'exists:outlets,id'],
        ]);

        $user = $request->user();
        $outletId = (int) $request->input('outlet_id');

        if (! $user->outletRoles()->where('outlet_id', $outletId)->where('status', 'active')->exists()) {
            abort(403, __('Anda tidak memiliki akses ke outlet tersebut.'));
        }

        $request->session()->put('active_outlet_id', $outletId);
        OutletScope::setActiveOutletId($outletId);

        $outlet = Outlet::find($outletId);
        $role = $user->outletRoles()->where('outlet_id', $outletId)->first();
        OutletContext::setCurrent($outlet, $role);

        return back()->with('success', __('Outlet aktif diperbarui.'));
    }
}
