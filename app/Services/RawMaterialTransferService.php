<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\RawMaterialTransfer;
use App\Scopes\OutletScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RawMaterialTransferService
{
    public function __construct(private InventoryService $inventory) {}

    public function transfer(
        RawMaterial $source,
        RawMaterial $target,
        float $qty,
        ?string $notes = null,
        ?\DateTimeInterface $occurredAt = null
    ): RawMaterialTransfer {
        $qty = round($qty, 4);

        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'qty' => __('Jumlah transfer harus lebih besar dari 0.'),
            ]);
        }

        if ($source->id === $target->id) {
            throw ValidationException::withMessages([
                'destination_raw_material_id' => __('Bahan tujuan tidak boleh sama dengan bahan sumber.'),
            ]);
        }

        $occurredAt = $occurredAt ? Carbon::instance($occurredAt) : now();

        return DB::transaction(function () use ($source, $target, $qty, $notes, $occurredAt) {
            $source = RawMaterial::withoutGlobalScope(OutletScope::class)
                ->lockForUpdate()
                ->findOrFail($source->id);
            $target = RawMaterial::withoutGlobalScope(OutletScope::class)
                ->lockForUpdate()
                ->findOrFail($target->id);

            if ($source->outlet_id === $target->outlet_id) {
                throw ValidationException::withMessages([
                    'destination_outlet_id' => __('Outlet tujuan harus berbeda dengan outlet asal.'),
                ]);
            }

            $available = (float) $source->stock_qty;
            if ($available < $qty) {
                throw ValidationException::withMessages([
                    'qty' => __('Stok tidak mencukupi. Stok tersedia: :qty', ['qty' => number_format($available, 1, ',', '.')]),
                ]);
            }

            $unitCost = (float) $source->unit_cost;

            $transfer = RawMaterialTransfer::create([
                'raw_material_from_id' => $source->id,
                'raw_material_to_id' => $target->id,
                'outlet_from_id' => $source->outlet_id,
                'outlet_to_id' => $target->outlet_id,
                'qty' => $qty,
                'notes' => $notes,
                'initiated_by' => Auth::id(),
                'transferred_at' => $occurredAt,
            ]);

            $movementOut = $this->inventory->adjustStock(
                $source,
                -1 * $qty,
                'adjustment',
                $unitCost,
                'transfer',
                $transfer->id,
                $notes,
                $occurredAt,
                null,
                'transfer_out'
            );

            $movementIn = $this->inventory->adjustStock(
                $target,
                $qty,
                'adjustment',
                $unitCost,
                'transfer',
                $transfer->id,
                $notes,
                $occurredAt,
                null,
                'transfer_in'
            );

            $transfer->movement_out_id = $movementOut->id;
            $transfer->movement_in_id = $movementIn->id;
            $transfer->save();

            return $transfer;
        });
    }
}

