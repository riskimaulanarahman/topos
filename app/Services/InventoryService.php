<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RawMaterial;
use App\Models\RawMaterialMovement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Adjust stock with moving average valuation when adding stock.
     */
    public function adjustStock(
        RawMaterial $material,
        float $qtyChange,
        string $type,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?\DateTimeInterface $occurredAt = null,
        ?float $countedQty = null,
        ?string $adjustmentReason = null
    ): RawMaterialMovement {
        return DB::transaction(function () use ($material, $qtyChange, $type, $unitCost, $referenceType, $referenceId, $notes, $occurredAt, $countedQty, $adjustmentReason) {
            if (!in_array($type, ['adjustment','purchase','production_consume','return'])) {
                throw new InvalidArgumentException('Invalid movement type');
            }

            $currentQty = (float) $material->stock_qty;
            $currentAvg = (float) $material->unit_cost;

            // If adding stock (purchase/adjustment positive), update moving average
            if ($qtyChange > 0 && in_array($type, ['purchase','adjustment'])) {
                $newQty = $currentQty + $qtyChange;
                $incomingCost = $unitCost ?? $currentAvg;
                if ($newQty <= 0) {
                    // Edge case: all consumed after update
                    $material->stock_qty = 0;
                    $material->unit_cost = $incomingCost; // keep last known cost
                } else {
                    $newAvg = (($currentQty * $currentAvg) + ($qtyChange * $incomingCost)) / $newQty;
                    $material->unit_cost = round($newAvg, 4);
                    $material->stock_qty = round($newQty, 4);
                }
            } else {
                // Consumption or negative adjustment: keep avg cost, just decrement stock
                $material->stock_qty = round($currentQty + $qtyChange, 4); // qtyChange may be negative
            }

            $material->save();

            $movement = new RawMaterialMovement([
                'raw_material_id' => $material->id,
                'type' => $type,
                'qty_change' => $qtyChange,
                'counted_qty' => $countedQty,
                'unit_cost' => $unitCost ?? $material->unit_cost,
                'adjustment_reason' => $adjustmentReason,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'occurred_at' => $occurredAt?->format('Y-m-d H:i:s') ?? now(),
                'created_by' => Auth::id(),
            ]);
            $movement->save();

            Log::info('Raw material movement recorded', [
                'material_id' => $material->id,
                'type' => $type,
                'qty_change' => $qtyChange,
                'unit_cost' => $movement->unit_cost,
                'counted_qty' => $countedQty,
                'adjustment_reason' => $adjustmentReason,
                'stock_after' => $material->stock_qty,
            ]);

            // Minimum stock alert: notify admins when crossing threshold
            try {
                $min = (float) $material->min_stock;
                if ($min > 0 && $currentQty > $min && (float)$material->stock_qty <= $min) {
                    $recipients = $this->resolveLowStockRecipients($material);
                    if ($recipients->isNotEmpty()) {
                        $payload = [[
                            'name' => $material->name,
                            'sku' => $material->sku,
                            'stock' => (float) $material->stock_qty,
                            'min' => $min,
                        ]];
                        foreach ($recipients as $recipient) {
                            $recipient->notify(new \App\Notifications\LowStockAlert($payload));
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed sending low stock alerts: '.$e->getMessage());
            }

            return $movement;
        });
    }

    private function resolveLowStockRecipients(RawMaterial $material)
    {
        $ids = collect();

        $movementUserIds = RawMaterialMovement::query()
            ->where('raw_material_id', $material->id)
            ->whereNotNull('created_by')
            ->pluck('created_by');

        if ($movementUserIds->isNotEmpty()) {
            $ids = $ids->merge($movementUserIds);
        }

        $expenseUserIds = Expense::query()
            ->whereHas('items', function ($query) use ($material) {
                $query->where('raw_material_id', $material->id);
            })
            ->pluck('created_by');

        if ($expenseUserIds->isNotEmpty()) {
            $ids = $ids->merge($expenseUserIds);
        }

        if ($ids->isEmpty() && Auth::id()) {
            $ids->push(Auth::id());
        }

        $ids = $ids->unique()->filter();

        if ($ids->isEmpty()) {
            return collect();
        }

        return User::whereIn('id', $ids)
            ->where('roles', '!=', 'admin')
            ->get();
    }
}
