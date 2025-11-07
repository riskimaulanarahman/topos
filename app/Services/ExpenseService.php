<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\RawMaterial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ExpenseService
{
    public function __construct(private InventoryService $inventory)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function create(array $attributes, array $items, ?UploadedFile $attachment = null): Expense
    {
        return DB::transaction(function () use ($attributes, $items, $attachment) {
            if ($attachment) {
                $attributes['attachment_path'] = $this->storeAttachment($attachment);
            }

            $expense = new Expense($attributes);
            $expense->amount = 0;
            $expense->save();

            $total = $this->syncItems($expense, $items, $expense->date?->startOfDay());
            $expense->amount = $total;
            $expense->save();

            return $expense->load(['category', 'items.rawMaterial']);
        });
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function update(Expense $expense, array $attributes, array $items, ?UploadedFile $attachment = null): Expense
    {
        return DB::transaction(function () use ($expense, $attributes, $items, $attachment) {
            $previousOccurredAt = $expense->date?->startOfDay();
            $removeAttachment = (bool) ($attributes['remove_attachment'] ?? false);
            unset($attributes['remove_attachment']);

            if ($attachment) {
                if ($expense->attachment_path) {
                    Storage::delete($expense->attachment_path);
                }
                $attributes['attachment_path'] = $this->storeAttachment($attachment);
            } elseif ($removeAttachment && $expense->attachment_path) {
                Storage::delete($expense->attachment_path);
                $attributes['attachment_path'] = null;
            }

            $expense->fill($attributes);
            $expense->save();

            $this->reverseExistingItems($expense, $previousOccurredAt);

            $total = $this->syncItems($expense->fresh(), $items, $expense->date?->startOfDay());
            $expense->amount = $total;
            $expense->save();

            return $expense->load(['category', 'items.rawMaterial']);
        });
    }

    public function delete(Expense $expense): void
    {
        DB::transaction(function () use ($expense) {
            $this->reverseExistingItems($expense, $expense->date?->startOfDay());

            if ($expense->attachment_path) {
                Storage::delete($expense->attachment_path);
            }

            $expense->delete();
        });
    }

    private function reverseExistingItems(Expense $expense, ?\DateTimeInterface $occurredAt = null): void
    {
        $expense->loadMissing('items.rawMaterial');
        foreach ($expense->items as $item) {
            if ($item->raw_material_id && $item->qty > 0) {
                $material = $item->rawMaterial;
                if ($material) {
                    $this->inventory->adjustStock(
                        $material,
                        -1 * (float) $item->qty,
                        'adjustment',
                        (float) $item->unit_cost,
                        'expense_item',
                        $item->id,
                        'Reversal of expense item: ' . ($item->description ?: $material->name),
                        $occurredAt
                    );
                }
            }
            $item->delete();
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function syncItems(Expense $expense, array $items, ?\DateTimeInterface $occurredAt): float
    {
        $total = 0.0;
        foreach ($items as $payload) {
            $itemData = $this->normalizeItemPayload($payload);
            $itemData['expense_id'] = $expense->id;

            /** @var ExpenseItem $item */
            $item = ExpenseItem::create($itemData);

            $total += (float) $item->total_cost;

            if ($item->raw_material_id) {
                $material = RawMaterial::find($item->raw_material_id);
                if (! $material) {
                    throw new InvalidArgumentException('Raw material not found for expense item.');
                }

                $this->inventory->adjustStock(
                    $material,
                    (float) $item->qty,
                    'purchase',
                    (float) $item->unit_cost,
                    'expense_item',
                    $item->id,
                    $item->notes,
                    $occurredAt
                );
            }
        }

        return round($total, 2);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizeItemPayload(array $payload): array
    {
        $rawMaterialId = $payload['raw_material_id'] ?? null;
        $qty = round((float) ($payload['qty'] ?? 0), 4);
        $unitCostInput = $payload['unit_cost'] ?? null;
        $totalPriceInput = $payload['item_price'] ?? ($payload['total_cost'] ?? null);
        $totalPrice = $totalPriceInput !== null ? round((float) $totalPriceInput, 2) : null;
        $description = $payload['description'] ?? null;
        $notes = $payload['notes'] ?? null;
        $unit = $payload['unit'] ?? null;

        if ($rawMaterialId) {
            $material = RawMaterial::find($rawMaterialId);
            if (! $material) {
                throw new InvalidArgumentException('Raw material not found.');
            }
            $description = $description ?: $material->name;
            $unit = $unit ?: $material->unit;
        }

        if (! $rawMaterialId && ! $description) {
            throw new InvalidArgumentException('Expense item description is required.');
        }

        if ($qty <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        if ($unitCostInput !== null && (float)$unitCostInput < 0) {
            throw new InvalidArgumentException('Unit cost must be zero or greater.');
        }

        if ($totalPrice !== null && $totalPrice < 0) {
            throw new InvalidArgumentException('Item price must be zero or greater.');
        }

        $unitCost = null;
        if ($unitCostInput !== null) {
            $unitCost = round((float) $unitCostInput, 4);
        } elseif ($totalPrice !== null && $qty > 0) {
            $unitCost = round($totalPrice / $qty, 4);
        }

        if ($unitCost === null) {
            throw new InvalidArgumentException('Unable to determine unit cost for expense item.');
        }

        $total = $totalPrice !== null ? $totalPrice : round($qty * $unitCost, 2);

        return [
            'raw_material_id' => $rawMaterialId,
            'description' => $description,
            'unit' => $unit,
            'qty' => $qty,
            'unit_cost' => $unitCost,
            'total_cost' => $total,
            'notes' => $notes,
        ];
    }

    private function storeAttachment(UploadedFile $attachment): string
    {
        $diskPath = $attachment->store('public/expense_attachments');
        if (! $diskPath) {
            throw new InvalidArgumentException('Failed to store expense attachment.');
        }

        return $diskPath;
    }
}
