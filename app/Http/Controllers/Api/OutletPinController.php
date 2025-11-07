<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\OutletUserRole;
use App\Scopes\OutletScope;
use App\Support\OutletContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OutletPinController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'outlet_id' => ['required', 'integer', 'exists:outlets,id'],
            'pin' => ['required', 'string'],
        ]);

        $user = $request->user();

        /** @var OutletUserRole|null $member */
        $member = OutletUserRole::with('outlet')
            ->where('outlet_id', $data['outlet_id'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $member) {
            abort(403, __('Anda tidak memiliki akses ke outlet tersebut.'));
        }

        if (! $member->hasPin()) {
            throw ValidationException::withMessages([
                'pin' => __('PIN belum disetel untuk outlet ini. Silakan hubungi owner outlet.'),
            ]);
        }

        if (! $member->verifyPin($data['pin'])) {
            throw ValidationException::withMessages([
                'pin' => __('PIN tidak valid.'),
            ]);
        }

        $member->save();

        OutletScope::setActiveOutletId($member->outlet_id);
        OutletContext::setCurrent($member->outlet, $member);

        return response()->json([
            'success' => true,
            'message' => __('PIN valid. Akses outlet diizinkan.'),
            'outlet' => [
                'id' => $member->outlet?->id,
                'name' => $member->outlet?->name,
            ],
            'pin_last_verified_at' => $member->pin_last_verified_at?->toIso8601String(),
        ]);
    }
}

