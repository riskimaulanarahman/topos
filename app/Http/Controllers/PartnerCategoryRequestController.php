<?php

namespace App\Http\Controllers;

use App\Http\Requests\Outlet\PartnerCategoryRequestStoreRequest;
use App\Models\Outlet;
use App\Models\OutletUserRole;
use App\Models\PartnerCategoryAssignment;
use App\Models\PartnerCategoryChangeRequest;
use App\Services\PartnerCategoryAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PartnerCategoryRequestController extends Controller
{
    public function __construct(private PartnerCategoryAccessService $accessService)
    {
    }

    public function index(Request $request, Outlet $outlet)
    {
        $this->authorize('manageMembers', $outlet);

        $requests = PartnerCategoryChangeRequest::where('outlet_id', $outlet->id)
            ->with(['target.user:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('pages.outlets.category_requests.index', compact('outlet', 'requests'));
    }

    public function store(PartnerCategoryRequestStoreRequest $request, Outlet $outlet, OutletUserRole $member): RedirectResponse
    {
        $this->accessService->ensureMemberBelongsToOutlet($member, $outlet);

        $payload = $request->validatedPayload();

        $this->accessService->createChangeRequest(
            outlet: $outlet,
            requester: $request->user(),
            member: $member,
            payload: $payload
        );

        return back()->with('success', __('Permintaan perubahan akses kategori berhasil diajukan.'));
    }

    public function approve(Request $request, Outlet $outlet, PartnerCategoryChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('manageMembers', $outlet);
        $this->accessService->ensureRequestBelongsToOutlet($changeRequest, $outlet);

        $this->accessService->approveChangeRequest($changeRequest, $request->user());

        return back()->with('success', __('Permintaan akses kategori disetujui.'));
    }

    public function reject(Request $request, Outlet $outlet, PartnerCategoryChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('manageMembers', $outlet);
        $this->accessService->ensureRequestBelongsToOutlet($changeRequest, $outlet);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->accessService->rejectChangeRequest(
            changeRequest: $changeRequest,
            reviewer: $request->user(),
            notes: $request->input('reason')
        );

        return back()->with('success', __('Permintaan akses kategori ditolak.'));
    }
}
