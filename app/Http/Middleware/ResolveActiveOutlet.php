<?php

namespace App\Http\Middleware;

use App\Scopes\OutletScope;
use App\Support\OutletContext;
use Closure;
use Illuminate\Http\Request;

class ResolveActiveOutlet
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $outletId = $request->input('outlet_id') ?: $request->session()->get('active_outlet_id');

        if ($outletId && $user->outletRoles()->where('outlet_id', $outletId)->where('status', 'active')->exists()) {
            $request->session()->put('active_outlet_id', $outletId);
        } else {
            $outletId = $user->outletRoles()
                ->where('status', 'active')
                ->orderByRaw("role = 'owner' DESC")
                ->value('outlet_id');

            if ($outletId) {
                $request->session()->put('active_outlet_id', $outletId);
            }
        }

        $activeOutletId = $request->session()->get('active_outlet_id');
        OutletScope::setActiveOutletId($activeOutletId);

        if ($activeOutletId) {
            $outlet = $user->outlets()->where('outlets.id', $activeOutletId)->first();
            $role = $user->outletRoles()->where('outlet_id', $activeOutletId)->first();
            OutletContext::setCurrent($outlet, $role);
        } else {
            OutletContext::setCurrent(null, null);
        }

        return $next($request);
    }
}
