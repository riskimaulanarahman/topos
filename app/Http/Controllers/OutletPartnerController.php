<?php

namespace App\Http\Controllers;

use App\Http\Requests\Outlet\InvitePartnerRequest;
use App\Http\Requests\Outlet\UpdatePartnerPermissionsRequest;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\OutletUserRole;
use App\Services\PartnerInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OutletPartnerController extends Controller
{
    public function __construct(private PartnerInvitationService $invitationService)
    {
    }

    public function index(Request $request, Outlet $outlet)
    {
        $this->authorize('view', $outlet);

        $members = $outlet->members()
            ->with(['user:id,name,email', 'categoryAssignments.category:id,name'])
            ->orderBy('role')
            ->orderBy('status')
            ->paginate(15);

        $categories = Category::orderBy('name')->get();

        return view('pages.outlets.partners.index', compact('outlet', 'members', 'categories'));
    }

    public function store(InvitePartnerRequest $request, Outlet $outlet): RedirectResponse
    {
        $this->authorize('manageMembers', $outlet);

        $this->invitationService->invite(
            outlet: $outlet,
            inviter: $request->user(),
            email: $request->input('email'),
            permissions: $request->permissions()
        );

        return back()->with('success', __('Undangan mitra telah dikirim.'));
    }

    public function updatePermissions(UpdatePartnerPermissionsRequest $request, Outlet $outlet, OutletUserRole $member): RedirectResponse
    {
        $this->authorize('manageMembers', $outlet);

        abort_unless($member->outlet_id === $outlet->id, 404);

        $member->update($request->permissions());

        return back()->with('success', __('Izin mitra berhasil diperbarui.'));
    }

    public function destroy(Request $request, Outlet $outlet, OutletUserRole $member): RedirectResponse
    {
        $this->authorize('manageMembers', $outlet);

        abort_unless($member->outlet_id === $outlet->id, 404);

        if ($member->role === 'owner') {
            return back()->withErrors(__('Tidak dapat menghapus owner outlet.'));
        }

        $member->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        return back()->with('success', __('Akses mitra telah dicabut.'));
    }
}
