<?php

namespace App\Services;

use App\Jobs\SendCashierSummaryEmail;
use App\Models\CashierClosureReport;
use App\Models\CashierOutflow;
use App\Models\CashierSession;
use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class CashierSummaryService
{
    public function generate(
        CashierSession $session,
        ?string $timezone = null,
        ?int $timezoneOffset = null
    ): array {
        $timezone = $timezone ?: $session->getAttribute('timezone');
        $timezoneOffset = $timezoneOffset ?? $session->getAttribute('timezone_offset');

        $openedAtLocal = $this->convertToLocal($session->opened_at, $timezone, $timezoneOffset);
        $closedAtLocal = $this->convertToLocal($session->closed_at, $timezone, $timezoneOffset);

        if ($timezone && $timezoneOffset === null) {
            $timezoneOffset = $closedAtLocal?->utcOffset() ?? $openedAtLocal?->utcOffset();
        }

        if (!$timezone && $timezoneOffset === null) {
            $timezone = config('app.timezone');
            $openedAtLocal = $this->convertToLocal($session->opened_at, $timezone, null);
            $closedAtLocal = $this->convertToLocal($session->closed_at, $timezone, null);
            $timezoneOffset = $closedAtLocal?->utcOffset() ?? $openedAtLocal?->utcOffset();
        }

        $start = $session->opened_at ?? $session->created_at;
        $end = $session->closed_at ?? now();

        $orders = Order::with([
                'orderItems.product',
                'orderItems.variantSelections.optionItem',
                'orderItems.addonSelections.addon',
                'orderItems.addonSelections.optionItem',
            ])
            ->where('user_id', $session->user_id)
            ->when($session->outlet_id, fn ($query) => $query->where('outlet_id', $session->outlet_id))
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $nonRefundOrders = $orders->filter(fn ($order) => $order->status !== 'refund');
        $refundOrders = $orders->filter(fn ($order) => $order->status === 'refund');

        $paymentBreakdown = [];
        $cashSales = 0.0;
        foreach ($nonRefundOrders->groupBy('payment_method') as $method => $collection) {
            $methodLabel = strtoupper($method ?? 'UNKNOWN');
            $amount = (float) $collection->sum('total_price');
            if ($methodLabel === 'CASH') {
                $cashSales = $amount;
            }

            $paymentBreakdown[] = [
                'method' => $methodLabel,
                'amount' => $amount,
                'transactions' => $collection->count(),
            ];
        }

        $outflows = CashierOutflow::where('cashier_session_id', $session->id)->get();
        $outflowTotal = (float) $outflows->sum('amount');
        $outflowBreakdown = $outflows
            ->groupBy(function ($outflow) {
                return $outflow->category ?: 'other';
            })
            ->map(function ($collection, $category) {
                $categoryKey = $category ?: 'other';
                return [
                    'category' => $categoryKey,
                    'label' => $this->formatCategoryLabel($categoryKey),
                    'total' => (float) $collection->sum('amount'),
                    'count' => $collection->count(),
                ];
            })
            ->values()
            ->all();

        $totalSales = (float) $orders->sum('total_price');
        $refundTotal = (float) $refundOrders->sum(function ($order) {
            return $order->refund_nominal ?? $order->total_price ?? 0;
        });

        $cashRefunds = (float) $refundOrders
            ->filter(function ($order) {
                return strtolower($order->refund_method ?? '') === 'cash';
            })
            ->sum(function ($order) {
                return $order->refund_nominal ?? $order->total_price ?? 0;
            });

        $openingBalance = (float) ($session->opening_balance ?? 0);
        $countedCash = (float) ($session->closing_balance ?? 0);
        $expectedCash = $openingBalance + $cashSales - $cashRefunds - $outflowTotal;

        $summary = [
            'session' => [
                'id' => $session->id,
                'opened_at' => $openedAtLocal?->toIso8601String(),
                'closed_at' => $closedAtLocal?->toIso8601String(),
                'opening_balance' => $openingBalance,
                'closing_balance' => $countedCash,
                'remarks' => $session->remarks,
                'timezone' => $timezone,
                'timezone_offset' => $timezoneOffset,
            ],
            'totals' => [
                'sales' => $totalSales,
                'refunds' => $refundTotal,
                'net_sales' => $totalSales - $refundTotal,
            ],
            'payments' => $paymentBreakdown,
            'transactions' => [
                'total' => $orders->count(),
                'completed' => $nonRefundOrders->count(),
                'refunded' => $refundOrders->count(),
            ],
            'outflows' => [
                'total' => $outflowTotal,
                'count' => $outflows->count(),
                'by_category' => $outflowBreakdown,
            ],
            'cash_balance' => [
                'opening' => $openingBalance,
                'cash_sales' => $cashSales,
                'cash_refunds' => $cashRefunds,
                'cash_outflows' => $outflowTotal,
                'expected' => $expectedCash,
                'counted' => $countedCash,
                'difference' => $countedCash - $expectedCash,
            ],
        ];

        $summary = $this->appendProductSales($summary, $nonRefundOrders);

        return $summary;
    }

    private function appendProductSales(array $summary, $orders): array
    {
        $productAggregates = [];
        $addonAggregates = [];
        $productTotalQuantity = 0;
        $productTotalNet = 0.0;
        $addonTotalQuantity = 0;
        $addonTotalNet = 0.0;

        foreach ($orders as $order) {
            foreach ($order->orderItems ?? [] as $item) {
                $quantity = max(1, (int) $item->quantity);
                $product = $item->product;
                $name = $product?->name ?? ($item->product_name ?? 'Produk Tidak Dikenal');
                $sku = $product?->sku ?? ($item->product_sku ?? null);
                $variantNames = $item->variantSelections
                    ->map(function ($selection) {
                        $variantName = $selection->variant_name;
                        $optionName = optional($selection->optionItem)->name;
                        return trim($variantName ?: $optionName ?: '');
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                $variantLabel = empty($variantNames) ? null : implode(', ', $variantNames);

                $grossUnit = (float) ($item->unit_price_before_discount
                    ?? $item->unit_price_after_discount
                    ?? $item->total_price);
                $grossTotal = $grossUnit * $quantity;
                if ($grossTotal <= 0) {
                    $grossTotal = (float) $item->total_price + (float) ($item->discount_amount ?? 0);
                }
                $netUnit = (float) ($item->unit_price_after_discount
                    ?? $item->unit_price_before_discount
                    ?? ($quantity > 0 ? $item->total_price / $quantity : $item->total_price));
                if ($netUnit <= 0) {
                    $netUnit = $grossUnit;
                }
                $netTotal = $netUnit * $quantity;
                $discountTotal = max(0.0, $grossTotal - $netTotal);

                $key = ($item->product_id ?? 'unknown') . '|' . ($variantLabel ?? '-');
                if (!isset($productAggregates[$key])) {
                    $productAggregates[$key] = [
                        'product_id' => $item->product_id,
                        'name' => $name,
                        'sku' => $sku,
                        'variant' => $variantLabel,
                        'quantity' => 0,
                        'gross_total' => 0.0,
                        'discount_total' => 0.0,
                        'net_total' => 0.0,
                        'is_addon' => false,
                    ];
                }

                $productAggregates[$key]['quantity'] += $quantity;
                $productAggregates[$key]['gross_total'] += $grossTotal;
                $productAggregates[$key]['discount_total'] += $discountTotal;
                $productAggregates[$key]['net_total'] += $netTotal;

                $productTotalQuantity += $quantity;
                $productTotalNet += $netTotal;

                foreach ($item->addonSelections ?? [] as $addon) {
                    $addonQuantity = max(1, (int) ($addon->quantity ?? 1));
                    $addonName = $addon->addon_name
                        ?? $addon->addon?->name
                        ?? $addon->optionItem?->name
                        ?? 'Add-on';
                    $addonGroup = $addon->addon_group_name
                        ?? $addon->addon?->group_name
                        ?? null;
                    $addonSku = $addon->addon?->sku ?? $addon->optionItem?->sku ?? null;
                    $addonPrice = (float) ($addon->price_adjustment ?? 0.0);
                    $addonGross = $addonPrice * $addonQuantity;
                    $addonNet = $addonGross;

                    $addonKey = 'addon|' . ($addon->product_addon_id ?? 'option_' . ($addon->option_item_id ?? $addonName)) . '|' . ($addonGroup ?? '-');
                    if (!isset($addonAggregates[$addonKey])) {
                        $addonAggregates[$addonKey] = [
                            'product_id' => $addon->product_addon_id,
                            'name' => $addonName,
                            'sku' => $addonSku,
                            'variant' => $addonGroup,
                            'quantity' => 0,
                            'gross_total' => 0.0,
                            'discount_total' => 0.0,
                            'net_total' => 0.0,
                            'is_addon' => true,
                        ];
                    }

                    $addonAggregates[$addonKey]['quantity'] += $addonQuantity;
                    $addonAggregates[$addonKey]['gross_total'] += $addonGross;
                    $addonAggregates[$addonKey]['net_total'] += $addonNet;

                    $addonTotalQuantity += $addonQuantity;
                    $addonTotalNet += $addonNet;
                }
            }
        }

        $products = array_values(array_map(function ($item) {
            $item['gross_total'] = round($item['gross_total'], 2);
            $item['discount_total'] = round($item['discount_total'], 2);
            $item['net_total'] = round($item['net_total'], 2);
            return $item;
        }, $productAggregates));

        $addons = array_values(array_map(function ($item) {
            $item['gross_total'] = round($item['gross_total'], 2);
            $item['discount_total'] = round($item['discount_total'], 2);
            $item['net_total'] = round($item['net_total'], 2);
            return $item;
        }, $addonAggregates));

        $summary['sales_items'] = array_merge($products, $addons);
        $summary['sales_items_total_quantity'] = $productTotalQuantity;
        $summary['sales_items_total_net'] = round($productTotalNet, 2);
        $summary['sales_addons_total_quantity'] = $addonTotalQuantity;
        $summary['sales_addons_total_net'] = round($addonTotalNet, 2);
        $summary['sales_products_total_quantity'] = $productTotalQuantity;
        $summary['sales_products_total_net'] = round($productTotalNet, 2);
        $summary['sales_summary'] = [
            'products' => [
                'quantity' => $productTotalQuantity,
                'net' => round($productTotalNet, 2),
            ],
            'addons' => [
                'quantity' => $addonTotalQuantity,
                'net' => round($addonTotalNet, 2),
            ],
        ];

        return $summary;
    }

    private function formatCategoryLabel(string $category): string
    {
        return Str::of($category)
            ->replace('_', ' ')
            ->lower()
            ->title();
    }

    private function convertToLocal(?Carbon $dateTime, ?string $timezone, ?int $timezoneOffset): ?Carbon
    {
        if (!$dateTime) {
            return null;
        }

        $instance = $dateTime->copy();

        if ($timezone) {
            return $instance->setTimezone($timezone);
        }

        if ($timezoneOffset !== null) {
            return $instance->setTimezone('UTC')->addMinutes($timezoneOffset);
        }

        return $instance;
    }

    public function createReport(
        CashierSession $session,
        array $summary,
        ?string $emailTo = null
    ): CashierClosureReport {
        return CashierClosureReport::updateOrCreate(
            ['cashier_session_id' => $session->id],
            [
                'user_id' => $session->user_id,
                'summary' => $summary,
                'email_to' => $emailTo,
                'email_status' => $emailTo ? 'pending' : 'skipped',
            ]
        );
    }

    public function dispatchEmail(CashierClosureReport $report): void
    {
        if (!$report->email_to) {
            return;
        }

        SendCashierSummaryEmail::dispatch($report->id);
    }
}
