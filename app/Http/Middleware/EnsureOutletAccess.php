<?php

namespace App\Http\Middleware;

use App\Models\Outlet;
use App\Scopes\OutletScope;
use App\Support\OutletContext;
use Closure;
use Illuminate\Http\Request;

class EnsureOutletAccess
{
    public function handle(Request $request, Closure $next)
    {
        /** @var Outlet|null $outlet */
        $outlet = $request->route('outlet');

        if (! $outlet) {
            return $next($request);
        }

        if (! $request->user() || ! $request->user()->can('view', $outlet)) {
            abort(403);
        }

        OutletScope::setActiveOutletId($outlet->id);
        $request->session()->put('active_outlet_id', $outlet->id);
        $role = $request->user()->outletRoles()->where('outlet_id', $outlet->id)->first();
        OutletContext::setCurrent($outlet, $role);

        return $next($request);
    }
}
