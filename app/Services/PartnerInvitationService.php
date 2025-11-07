<?php

namespace App\Services;

use App\Events\PartnerInvitationCreated;
use App\Models\Outlet;
use App\Models\OutletUserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PartnerInvitationService
{
    public function invite(Outlet $outlet, User $inviter, string $email, array $permissions = []): OutletUserRole
    {
        /** @var User|null $invitee */
        $invitee = User::where('email', $email)->first();

        if (! $invitee) {
            throw new RuntimeException(__('Pengguna dengan email tersebut belum terdaftar.'));
        }

        if ($invitee->id === $inviter->id) {
            throw new RuntimeException(__('Anda tidak dapat mengundang diri sendiri sebagai mitra.'));
        }

        return DB::transaction(function () use ($outlet, $inviter, $invitee, $permissions) {
            $existing = OutletUserRole::where('outlet_id', $outlet->id)
                ->where('user_id', $invitee->id)
                ->where('role', 'partner')
                ->first();

            $token = Str::random(40);

            if ($existing) {
                $existing->update(array_merge([
                    'status' => 'pending',
                    'invitation_token' => $token,
                    'invitation_sent_at' => now(),
                    'accepted_at' => null,
                    'revoked_at' => null,
                    'created_by' => $inviter->id,
                ], $permissions));

                $member = $existing;
            } else {
                $member = OutletUserRole::create(array_merge([
                    'outlet_id' => $outlet->id,
                    'user_id' => $invitee->id,
                    'role' => 'partner',
                    'status' => 'pending',
                    'invitation_token' => $token,
                    'invitation_sent_at' => now(),
                    'created_by' => $inviter->id,
                ], $permissions));
            }

            PartnerInvitationCreated::dispatch($member);
            Log::info('Partner invitation created', [
                'outlet_id' => $outlet->id,
                'invitee_id' => $invitee->id,
                'inviter_id' => $inviter->id,
            ]);

            return $member;
        });
    }
}
