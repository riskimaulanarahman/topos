<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RawMaterialAdjustStockRequest;
use App\Http\Requests\RawMaterialStoreRequest;
use App\Http\Requests\RawMaterialUpdateRequest;
use App\Http\Resources\RawMaterialResource;
use App\Http\Resources\RawMaterialMovementResource;
use App\Models\RawMaterial;
use App\Services\InventoryService;
use App\Services\RawMaterialStockSummaryService;
use App\Services\RawMaterialTransferService;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\RawMaterialPurchaseRequest;
use App\Http\Requests\RawMaterialOpnameRequest;

class RawMaterialController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function index(Request $request)
    {
        Gate::authorize('inventory.manage');
        $query = RawMaterial::query()
            ->accessibleBy($request->user())
            ->with(['categories:id,name']);
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('sku', 'like', "%$s%");
            });
        }
        if ($request->boolean('low_stock_only')) {
            $query->whereColumn('stock_qty', '<=', 'min_stock');
        }
        $materials = $query->orderBy('name')->paginate($request->integer('page_size', 20));
        return RawMaterialResource::collection($materials);
    }

    public function store(RawMaterialStoreRequest $request)
    {
        Gate::authorize('inventory.manage');
        $data = $request->validated();
        $data['min_stock'] = $data['min_stock'] ?? null;
        $data['unit_cost'] = 0;
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);
        $material = RawMaterial::create($data);
        if (! empty($categoryIds)) {
            $material->categories()->sync($categoryIds);
        }
        $material->load('categories:id,name');
        return (new RawMaterialResource($material))
            ->additional(['message' => 'Raw material created']);
    }

    public function update(RawMaterialUpdateRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $data = $request->validated();
        if (! array_key_exists('min_stock', $data)) {
            $data['min_stock'] = $material->min_stock;
        }
        $categoryIds = array_key_exists('category_ids', $data) ? $data['category_ids'] : null;
        unset($data['category_ids']);
        $material->update($data);
        if (is_array($categoryIds)) {
            $material->categories()->sync($categoryIds);
        }
        $material->load('categories:id,name');
        return (new RawMaterialResource($material))
            ->additional(['message' => 'Raw material updated']);
    }

    public function destroy(int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);

        if ($material->expenseItems()->exists()) {
            return response()->json([
                'message' => 'Raw material is linked to expense items and cannot be deleted.',
            ], 422);
        }

        if ($material->recipeItems()->exists()) {
            return response()->json([
                'message' => 'Raw material is used in product recipes and cannot be deleted.',
            ], 422);
        }

        $material->delete();

        return response()->json(['message' => 'Raw material deleted']);
    }

    public function adjustStock(RawMaterialAdjustStockRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $qty = (float) $request->input('qty_change');
        $unitCost = $request->input('unit_cost');
        $movement = $this->inventory->adjustStock($material, $qty, $qty >= 0 ? 'adjustment' : 'adjustment', $unitCost, 'manual_adjustment', $material->id, $request->input('notes'));
        return (new RawMaterialMovementResource($movement))
            ->additional(['message' => 'Stock adjusted']);
    }

    public function purchase(RawMaterialPurchaseRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $data = $request->validated();
        $movement = $this->inventory->adjustStock(
            $material,
            (float)$data['qty'],
            'purchase',
            (float)$data['unit_cost'],
            'purchase',
            $material->id,
            $data['notes'] ?? null,
            $request->date('occurred_at')
        );
        return (new RawMaterialMovementResource($movement))
            ->additional(['message' => 'Stock purchased']);
    }

    public function stockOut(RawMaterialAdjustStockRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $qty = abs((float)$request->input('qty_change'));
        $movement = $this->inventory->adjustStock(
            $material,
            -1 * $qty,
            'adjustment',
            $request->input('unit_cost'),
            'waste',
            $material->id,
            $request->input('notes')
        );
        return (new RawMaterialMovementResource($movement))
            ->additional(['message' => 'Stock decremented']);
    }

    public function opname(RawMaterialOpnameRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $counted = (float) $request->input('counted_qty');
        $delta = $counted - (float)$material->stock_qty;
        if (abs($delta) < 1e-9) {
            return response()->json(['message' => 'No adjustment needed', 'data' => null]);
        }
        $movement = $this->inventory->adjustStock(
            $material,
            $delta,
            'adjustment',
            $material->unit_cost,
            'stock_opname',
            $material->id,
            $request->input('notes') ?? 'Stock opname'
        );
        return (new RawMaterialMovementResource($movement))
            ->additional(['message' => 'Opname adjusted']);
    }

    public function transfer(Request $request, int $id, RawMaterialTransferService $transferService)
    {
        Gate::authorize('inventory.manage');

        $material = RawMaterial::findOrFail($id);

        $role = OutletContext::currentRole($material->outlet_id);
        if (! $role || $role->role !== 'owner') {
            abort(403, __('Hanya owner yang dapat mentransfer stok.'));
        }

        $data = $request->validate([
            'destination_outlet_id' => ['required', 'integer'],
            'destination_raw_material_id' => ['required', 'integer'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $destinationOutletId = (int) $data['destination_outlet_id'];

        if ($destinationOutletId === (int) $material->outlet_id) {
            throw ValidationException::withMessages([
                'destination_outlet_id' => __('Pilih outlet tujuan yang berbeda.'),
            ]);
        }

        $user = $request->user();

        $ownsDestination = $user->ownedOutlets()
            ->where('outlets.id', $destinationOutletId)
            ->exists();

        if (! $ownsDestination) {
            throw ValidationException::withMessages([
                'destination_outlet_id' => __('Anda bukan owner di outlet tujuan.'),
            ]);
        }

        $destinationMaterial = RawMaterial::forOutlet($destinationOutletId)
            ->where('id', $data['destination_raw_material_id'])
            ->first();

        if (! $destinationMaterial) {
            throw ValidationException::withMessages([
                'destination_raw_material_id' => __('Bahan tujuan tidak ditemukan di outlet tersebut.'),
            ]);
        }

        if ($destinationMaterial->unit !== $material->unit) {
            throw ValidationException::withMessages([
                'destination_raw_material_id' => __('Satuan bahan tujuan harus sama.'),
            ]);
        }

        try {
            $transfer = $transferService->transfer(
                $material,
                $destinationMaterial,
                (float) $data['qty'],
                $data['notes'] ?? null
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json([
            'message' => __('Stok berhasil ditransfer.'),
            'data' => [
                'transfer_id' => $transfer->id,
                'qty' => (float) $transfer->qty,
                'transferred_at' => optional($transfer->transferred_at)->toISOString(),
            ],
        ]);
    }

    public function sendStockSummaryEmail(Request $request, RawMaterialStockSummaryService $stockSummaryService)
    {
        Gate::authorize('inventory.manage');

        $data = $request->validate([
            'near_threshold_percent' => ['nullable', 'numeric', 'min:0'],
            'subject' => ['nullable', 'string', 'max:255'],
            'recipient_emails' => ['nullable', 'array'],
            'recipient_emails.*' => ['email'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:10'],
        ]);

        $nearPercent = array_key_exists('near_threshold_percent', $data)
            ? ($data['near_threshold_percent'] !== null ? (float) $data['near_threshold_percent'] : null)
            : null;

        $subject = $data['subject'] ?? null;
        $recipientEmails = $data['recipient_emails'] ?? null;
        $timezone = $data['timezone'] ?? null;
        $locale = $data['locale'] ?? null;

        try {
            $result = $stockSummaryService->sendSummary($nearPercent, $recipientEmails, $subject, $timezone, $locale);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Gagal mengirim ringkasan stok.',
            ], 500);
        }

        return response()->json([
            'message' => 'Ringkasan stok telah dikirim.',
            'sent_to' => $result['sent_to'],
            'summary' => $result['summary'],
        ]);
    }
}
