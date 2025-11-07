<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\OutletContext;
use App\Services\PartnerCategoryAccessService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    //index
    public function index()
    {
        $userId = auth()->id();

        // get all order data from orders table and paginate it by 10 items per page
        $orders = Order::with('user')->orderBy('created_at', 'DESC')->where('user_id',$userId)->paginate(10);
        return view('pages.orders.index', compact('orders'));
    }

    //view (no longer used by links; kept for backward compatibility)
    public function show($id)
    {
        $order = \App\Models\Order::with('user')->where('id', $id)->first();
        $orderItems = \App\Models\OrderItem::with('product')->where('order_id', $id)->get();
        return view('pages.orders.view', compact('order', 'orderItems'));
    }

    // JSON details for modal
    public function showJson($id)
    {
        $user = auth()->user();
        $userId = $user?->id;

        $activeOutlet = OutletContext::currentOutlet();
        $currentRole = OutletContext::currentRole();

        $ownerUserIds = [$userId];
        $accessibleCategoryIds = null; // null means full access

        if ($currentRole && $currentRole->role === 'partner' && $activeOutlet) {
            $ownerUserIds = $activeOutlet->owners()->pluck('users.id')->unique()->values()->all();
            if (empty($ownerUserIds)) {
                $ownerUserIds = [$userId];
            }

            /** @var PartnerCategoryAccessService $access */
            $access = app(PartnerCategoryAccessService::class);
            $categoryIds = $access->accessibleCategoryIdsFor($user, $activeOutlet);
            if ($categoryIds !== ['*']) {
                $normalizedCategoryIds = array_values(
                    array_unique(
                        array_map('intval', array_filter((array) $categoryIds, fn($value) => $value !== null))
                    )
                );
                $accessibleCategoryIds = $normalizedCategoryIds;
                if (empty($accessibleCategoryIds)) {
                    abort(403, 'Kategori belum dibagikan untuk outlet ini.');
                }
            }
        }

        $order = Order::with([
                'user',
                'orderItems.product.category',
                'orderItems.addonSelections.addon',
                'orderItems.addonSelections.optionItem',
            ])
            ->whereIn('user_id', $ownerUserIds)
            ->findOrFail($id);

        $itemsCollection = $order->orderItems;
        if (is_array($accessibleCategoryIds)) {
            $itemsCollection = $itemsCollection->filter(function ($item) use ($accessibleCategoryIds) {
                $categoryId = optional($item->product)->category_id;
                if ($categoryId === null) {
                    return false;
                }

                $categoryId = (int) $categoryId;

                return in_array($categoryId, $accessibleCategoryIds, true);
            });

            if ($itemsCollection->isEmpty()) {
                abort(403, 'Anda tidak memiliki akses ke detail order ini.');
            }
        }

        $transactionTime = $order->transaction_time;
        if ($transactionTime instanceof CarbonInterface) {
            $transactionTimeIso = $transactionTime->toIso8601String();
        } elseif ($transactionTime) {
            $transactionTimeIso = Carbon::parse($transactionTime, config('app.timezone'))->toIso8601String();
        } else {
            $transactionTimeIso = null;
        }

        $addonsTotalQuantity = 0;
        $addonsTotalPrice = 0;

        $itemsPayload = $itemsCollection->map(function($item) use (&$addonsTotalQuantity, &$addonsTotalPrice) {
            $unitPrice = null;
            if ($item->quantity > 0) {
                $unitPrice = $item->total_price / $item->quantity;
            }

            $addons = $item->addonSelections->map(function ($addon) use (&$addonsTotalQuantity, &$addonsTotalPrice) {
                $quantity = max(1, (int) ($addon->quantity ?? 1));
                $unit = (int) ($addon->price_adjustment ?? 0);
                $total = $unit * $quantity;
                $addonsTotalQuantity += $quantity;
                $addonsTotalPrice += $total;

                return [
                    'name' => $addon->addon_name
                        ?? $addon->addon?->name
                        ?? $addon->optionItem?->name
                        ?? 'Add-on',
                    'group' => $addon->addon_group_name
                        ?? $addon->addon?->group_name
                        ?? null,
                    'sku' => $addon->addon?->sku
                        ?? $addon->optionItem?->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unit,
                    'total_price' => $total,
                ];
            })->values();

            return [
                'product_name' => optional($item->product)->name,
                'category_name' => optional(optional($item->product)->category)->name,
                'price' => $unitPrice,
                'quantity' => $item->quantity,
                'total_price' => $item->total_price,
                'addons' => $addons,
            ];
        })->values();

        return response()->json([
            'id' => $order->id,
            'transaction_number' => $order->transaction_number,
            'transaction_time' => $order->transaction_time,
            'transaction_time_iso' => $transactionTimeIso,
            'payment_method' => $order->payment_method,
            'status' => $order->status,
            'sub_total' => $order->sub_total,
            'discount_amount' => $order->discount_amount,
            'tax' => $order->tax,
            'service_charge' => $order->service_charge,
            'total_price' => $order->total_price,
            'total_item' => $order->total_item,
            'cashier' => optional($order->user)->name,
            'items' => $itemsPayload,
            'add_on_summary' => [
                'quantity' => $addonsTotalQuantity,
                'total_price' => $addonsTotalPrice,
            ],
        ]);
    }


}
