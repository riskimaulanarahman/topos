<?php

namespace App\Http\Controllers;

use App\Http\Requests\RawMaterialTransferRequest;
use App\Models\RawMaterial;
use App\Models\RawMaterialTransfer;
use App\Services\RawMaterialTransferService;
use App\Support\OutletContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RawMaterialTransferController extends Controller
{
    public function __construct(private RawMaterialTransferService $transferService)
    {
    }

    public function create(Request $request, RawMaterial $raw_material)
    {
        $role = OutletContext::currentRole();

        if (! $role || $role->role !== 'owner') {
            abort(403, __('Hanya owner yang dapat mentransfer stok.'));
        }

        $user = $request->user();
        $currentOutletId = $raw_material->outlet_id;

        $destinationOutlets = $user->ownedOutlets()
            ->where('outlets.id', '!=', $currentOutletId)
            ->orderBy('outlets.name')
            ->get(['outlets.id', 'outlets.name']);

        $materialsByOutlet = [];

        foreach ($destinationOutlets as $outlet) {
            $materialsByOutlet[$outlet->id] = RawMaterial::forOutlet($outlet->id)
                ->orderBy('name')
                ->get(['id', 'name', 'unit', 'stock_qty', 'sku'])
                ->map(fn ($material) => [
                    'id' => $material->id,
                    'name' => $material->name,
                    'unit' => $material->unit,
                    'stock_qty' => (float) $material->stock_qty,
                    'sku' => $material->sku,
                ])
                ->values();
        }

        $recentTransfers = RawMaterialTransfer::query()
            ->where(function (Builder $query) use ($raw_material) {
                $query->where('raw_material_from_id', $raw_material->id)
                    ->orWhere('raw_material_to_id', $raw_material->id);
            })
            ->with(['sourceOutlet:id,name', 'targetOutlet:id,name'])
            ->latest('transferred_at')
            ->limit(10)
            ->get();

        return view('pages.raw_materials.transfer', [
            'material' => $raw_material,
            'destinationOutlets' => $destinationOutlets,
            'materialsByOutlet' => $materialsByOutlet,
            'recentTransfers' => $recentTransfers,
        ]);
    }

    public function store(
        RawMaterialTransferRequest $request,
        RawMaterial $raw_material
    ): RedirectResponse {
        $role = OutletContext::currentRole();

        if (! $role || $role->role !== 'owner') {
            abort(403, __('Hanya owner yang dapat mentransfer stok.'));
        }

        $data = $request->validated();
        $destinationOutletId = (int) $data['destination_outlet_id'];

        if ($destinationOutletId === (int) $raw_material->outlet_id) {
            return back()
                ->withInput()
                ->withErrors(['destination_outlet_id' => __('Pilih outlet tujuan yang berbeda.')]);
        }

        $user = $request->user();

        $ownsDestination = $user->ownedOutlets()
            ->where('outlets.id', $destinationOutletId)
            ->exists();

        if (! $ownsDestination) {
            return back()
                ->withInput()
                ->withErrors(['destination_outlet_id' => __('Anda bukan owner di outlet tujuan.')]);
        }

        $destinationMaterial = RawMaterial::forOutlet($destinationOutletId)
            ->where('id', $data['destination_raw_material_id'])
            ->first();

        if (! $destinationMaterial) {
            return back()
                ->withInput()
                ->withErrors(['destination_raw_material_id' => __('Bahan tujuan tidak ditemukan di outlet tersebut.')]);
        }

        if ($destinationMaterial->unit !== $raw_material->unit) {
            return back()
                ->withInput()
                ->withErrors(['destination_raw_material_id' => __('Satuan bahan tujuan harus sama.')]);
        }

        try {
            $this->transferService->transfer(
                $raw_material,
                $destinationMaterial,
                (float) $data['qty'],
                $data['notes'] ?? null
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('raw-materials.index')
            ->with('success', __('Stok berhasil ditransfer.'));
    }
}
