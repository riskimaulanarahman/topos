<?php

namespace App\Http\Controllers;

use App\Http\Requests\Outlet\StoreOutletRequest;
use App\Http\Requests\Outlet\UpdateOutletRequest;
use App\Models\Outlet;
use App\Models\OutletUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OutletController extends Controller
{
    public function index(Request $request)
    {
        $outlets = $request->user()
            ->outlets()
            ->withPivot('role', 'status', 'can_manage_stock', 'can_manage_expense', 'can_manage_sales', 'accepted_at')
            ->orderBy('name')
            ->get();

        return view('pages.outlets.index', compact('outlets'));
    }

    public function create()
    {
        return view('pages.outlets.create');
    }

    public function store(StoreOutletRequest $request)
    {
        $user = $request->user();

        $outlet = DB::transaction(function () use ($user, $request) {
            $payload = $request->validated();
            $payload['created_by'] = $user->id;
            $payload['code'] = $payload['code'] ?? Str::upper(Str::slug($payload['name'] . '-' . Str::random(4), ''));

            $outlet = Outlet::create($payload);

            OutletUserRole::create([
                'outlet_id' => $outlet->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
                'can_manage_stock' => true,
                'can_manage_expense' => true,
                'can_manage_sales' => true,
                'accepted_at' => now(),
                'created_by' => $user->id,
            ]);

            return $outlet;
        });

        return redirect()
            ->route('outlets.show', $outlet)
            ->with('success', __('Outlet berhasil dibuat.'));
    }

    public function show(Outlet $outlet)
    {
        $this->authorize('view', $outlet);

        $members = $outlet->members()
            ->with('user:id,name,email')
            ->orderBy('role')
            ->orderBy('status')
            ->get();

        return view('pages.outlets.show', compact('outlet', 'members'));
    }

    public function edit(Outlet $outlet)
    {
        $this->authorize('update', $outlet);

        return view('pages.outlets.edit', compact('outlet'));
    }

    public function update(UpdateOutletRequest $request, Outlet $outlet)
    {
        $this->authorize('update', $outlet);

        $outlet->fill($request->validated());
        $outlet->save();

        return redirect()
            ->route('outlets.show', $outlet)
            ->with('success', __('Outlet berhasil diperbarui.'));
    }
}
