<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\ProductRecipe;
use App\Models\RawMaterialMovement;
use App\Services\InventoryService;

class ConsumeRawMaterialsOnOrderPaid
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function handle(OrderPaid $event): void
    {
        // Idempotency: jangan konsumsi ganda untuk order yang sama
        $exists = RawMaterialMovement::where('reference_type', 'order')
            ->where('reference_id', $event->orderId)
            ->exists();
        if ($exists) {
            return;
        }

        $order = \App\Models\Order::with(['orderItems.product'])->find($event->orderId);
        if (! $order) {
            return;
        }

        foreach ($order->orderItems as $item) {
            $recipe = ProductRecipe::with('items.rawMaterial')
                ->where('product_id', $item->product_id)
                ->first();
            if (! $recipe) {
                // Tidak ada resep: kurangi stok produk langsung
                if ($item->product && $item->quantity > 0) {
                    $item->product->decrement('stock', (int) $item->quantity);
                }
                continue;
            }

            foreach ($recipe->items as $ri) {
                $yield = max(1e-9, (float) $recipe->yield_qty);
                $kebutuhanPerUnit = ((float) $ri->qty_per_yield) * (1 + (float)$ri->waste_pct/100) / $yield;
                $totalKonsumsi = $kebutuhanPerUnit * (int) $item->quantity;
                if ($totalKonsumsi > 0) {
                    $this->inventory->adjustStock(
                        $ri->rawMaterial,
                        -1 * $totalKonsumsi,
                        'production_consume',
                        $ri->rawMaterial->unit_cost,
                        referenceType: 'order',
                        referenceId: $order->id,
                        notes: 'Consume on sale: '.$order->transaction_number
                    );
                }
            }
        }
    }
}
