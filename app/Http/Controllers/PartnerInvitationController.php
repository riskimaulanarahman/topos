<?php

namespace App\Http\Controllers;

use App\Models\OutletUserRole;
use Illuminate\Http\Request;

class PartnerInvitationController extends Controller
{
    public function show(Request $request, string $token)
    {
        $invitation = OutletUserRole::where('invitation_token', $token)->firstOrFail();

        return view('pages.outlets.partner_invitation', [
            'invitation' => $invitation->load('outlet', 'user'),
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $request->validate([
            'confirm' => ['accepted'],
        ]);

        $invitation = OutletUserRole::where('invitation_token', $token)->firstOrFail();

        abort_if($request->user()?->id !== $invitation->user_id, 403, __('Anda harus login dengan akun yang diundang.'));

        $invitation->update([
            'status' => 'active',
            'invitation_token' => null,
            'accepted_at' => now(),
        ]);

        return redirect()
            ->route('outlets.show', $invitation->outlet_id)
            ->with('success', __('Anda kini memiliki akses ke outlet tersebut.'));
    }
}
