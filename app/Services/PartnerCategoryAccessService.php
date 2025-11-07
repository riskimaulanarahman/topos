<?php

namespace App\Services;

use App\Events\PartnerCategoryRequestApproved;
use App\Events\PartnerCategoryRequestCreated;
use App\Events\PartnerCategoryRequestRejected;
use App\Models\Outlet;
use App\Models\OutletUserRole;
use App\Models\PartnerCategoryAssignment;
use App\Models\PartnerCategoryChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PartnerCategoryAccessService
{
    public function ensureMemberBelongsToOutlet(OutletUserRole $member, Outlet $outlet): void
    {
        if ($member->outlet_id !== $outlet->id) {
            abort(404);
        }
    }

    public function ensureRequestBelongsToOutlet(PartnerCategoryChangeRequest $changeRequest, Outlet $outlet): void
    {
        if ($changeRequest->outlet_id !== $outlet->id) {
            abort(404);
        }
    }

    public function createChangeRequest(Outlet $outlet, User $requester, OutletUserRole $member, array $payload): PartnerCategoryChangeRequest
    {
        if ($member->role === 'owner') {
            throw new RuntimeException(__('Owner outlet sudah memiliki akses penuh.'));
        }

        if ($requester->id !== $member->user_id && ! $requester->can('manageMembers', $outlet)) {
            throw new RuntimeException(__('Anda tidak berwenang mengubah akses kategori mitra ini.'));
        }

        $changeRequest = PartnerCategoryChangeRequest::create([
            'outlet_id' => $outlet->id,
            'target_outlet_user_role_id' => $member->id,
            'requested_by' => $requester->id,
            'payload' => $payload,
            'status' => 'pending',
        ]);
        PartnerCategoryRequestCreated::dispatch($changeRequest);

        Log::info('Partner category request created', [
            'change_request_id' => $changeRequest->id,
            'outlet_id' => $outlet->id,
            'member_id' => $member->id,
            'requested_by' => $requester->id,
        ]);

        return $changeRequest;
    }

    public function approveChangeRequest(PartnerCategoryChangeRequest $changeRequest, User $reviewer): void
    {
        DB::transaction(function () use ($changeRequest, $reviewer) {
            $payload = $changeRequest->payload ?? ['add' => [], 'remove' => []];
            $member = $changeRequest->target()->firstOrFail();

            foreach ($payload['remove'] ?? [] as $categoryId) {
                PartnerCategoryAssignment::where('outlet_user_role_id', $member->id)
                    ->where('category_id', $categoryId)
                    ->delete();
            }

            foreach ($payload['add'] ?? [] as $categoryId) {
                PartnerCategoryAssignment::updateOrCreate([
                    'outlet_user_role_id' => $member->id,
                    'category_id' => $categoryId,
                ], [
                    'approved_by' => $reviewer->id,
                    'approved_at' => now(),
                ]);
            }

            $changeRequest->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            PartnerCategoryRequestApproved::dispatch($changeRequest);
            Log::info('Partner category request approved', [
                'change_request_id' => $changeRequest->id,
                'reviewed_by' => $reviewer->id,
            ]);
        });
    }

    public function rejectChangeRequest(PartnerCategoryChangeRequest $changeRequest, User $reviewer, ?string $notes = null): void
    {
        $changeRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        PartnerCategoryRequestRejected::dispatch($changeRequest);
        Log::info('Partner category request rejected', [
            'change_request_id' => $changeRequest->id,
            'reviewed_by' => $reviewer->id,
        ]);
    }

    public function accessibleCategoryIdsFor(User $user, Outlet $outlet): array
    {
        $role = OutletUserRole::where('outlet_id', $outlet->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['active'])
            ->orderByDesc(DB::raw("role = 'owner'"))
            ->first();

        if (! $role) {
            return [];
        }

        if ($role->role === 'owner') {
            return $outlet->members()
                ->where('role', 'owner')
                ->exists()
                ? ['*'] : ['*'];
        }

        return PartnerCategoryAssignment::where('outlet_user_role_id', $role->id)
            ->pluck('category_id')
            ->unique()
            ->values()
            ->all();
    }
}
