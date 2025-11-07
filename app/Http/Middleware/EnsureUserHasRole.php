<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  array<int, string>  $roles  Allowed roles, e.g. ['admin']
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Fallback: seharusnya sudah dilindungi oleh 'auth'
        if (!$user) {
            abort(401, 'Unauthorized');
        }

        // roles disimpan pada kolom users.roles (enum: admin, staff, user)
        $userRole = $user->roles ?? null;

        // Jika role tidak ada dalam daftar yang diizinkan -> Forbidden
        if (!empty($roles) && !in_array($userRole, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}

