<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderPaid;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Outlet;
use App\Models\OutletUserRole;
use App\Models\Product;
use App\Models\OptionItem;
use App\Models\Discount;
use App\Models\DiscountProductRule;
use App\Scopes\OutletScope;
use App\Support\OutletContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->normalizePreferenceSelections($request);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.variants' => ['sometimes', 'array'],
            'items.*.variants.*.option_item_id' => ['required', 'integer', Rule::exists('option_items', 'id')],
            'items.*.addons' => ['sometimes', 'array'],
            'items.*.addons.*.option_item_id' => ['required', 'integer', Rule::exists('option_items', 'id')],
            'items.*.addons.*.quantity' => ['sometimes', 'integer', 'min:1'],
            'items.*.preferences' => ['sometimes', 'array'],
            'items.*.preferences.*.option_item_id' => ['required', 'integer', Rule::exists('option_items', 'id')],
            'items.*.preferences.*.quantity' => ['sometimes', 'integer', 'min:1'],
            'items.*.discount_id' => ['nullable', 'integer', Rule::exists('discounts', 'id')],
            'items.*.discount' => ['nullable', 'array'],
            'items.*.discount.id' => ['nullable', 'integer', Rule::exists('discounts', 'id')],
            'items.*.discount.type' => ['nullable', Rule::in(['percentage', 'fixed'])],
            'items.*.discount.value' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string'],
            'nominal_bayar' => ['nullable', 'numeric'],
            'outlet_id' => ['nullable', 'integer', Rule::exists('outlets', 'id')],
        ]);

        $user = $request->user();
        $userId = $user?->id;
        $outletId = $this->resolveAccessibleOutletId($request, true);
        OutletScope::setActiveOutletId($outletId);

        $preparedItems = $this->prepareOrderItems($validated['items'], $outletId, 'items');

        $order = DB::transaction(function () use ($request, $validated, $userId, $outletId, $preparedItems) {
            $totals = $this->calculateOrderTotals($preparedItems);

            $order = Order::create([
                'user_id' => $userId,
                'outlet_id' => $outletId,
                'transaction_number' => 'TRX-' . strtoupper(uniqid()),
                'transaction_time' => now(),
                'total_price' => $totals['total_price'],
                'total_item' => $totals['total_quantity'],
                'sub_total' => $totals['subtotal'],
                'discount' => $totals['total_discount'],
                'discount_amount' => $totals['total_discount'],
                'payment_method' => $request->input('payment_method', 'cash'),
                'nominal_bayar' => $request->input('nominal_bayar'),
            ]);

            foreach ($preparedItems as $payload) {
                $orderItem = $order->orderItems()->create([
                    'product_id' => $payload['product']->id,
                    'quantity' => $payload['quantity'],
                    'unit_price_before_discount' => $payload['unit_price_before_discount'],
                    'unit_price_after_discount' => $payload['unit_price_after_discount'],
                    'discount_amount' => $payload['discount_amount'],
                    'applied_discount_type' => $payload['applied_discount_type'],
                    'applied_discount_value' => $payload['applied_discount_value'],
                    'applied_discount_id' => $payload['applied_discount_id'],
                    'total_price' => $payload['total_price'],
                    'outlet_id' => $outletId,
                ]);

                $this->persistOrderItemOptions($orderItem, $payload, $order->user_id, $order->outlet_id);
            }

            return $order->fresh()->load($this->orderIncludes());
        });

        event(new OrderPaid($order->id));

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $this->transformOrder($order),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $outletId = $this->resolveAccessibleOutletId($request, true);
        OutletScope::setActiveOutletId($outletId);

        $orders = Order::with($this->orderIncludes())
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Order $order) => $this->transformOrder($order));

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function getAllOrder(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $outletId = $this->resolveAccessibleOutletId($request, true);
        OutletScope::setActiveOutletId($outletId);

        $orders = Order::with($this->orderIncludes())
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Order $order) => $this->transformOrder($order));

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ]);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $this->normalizePreferenceSelections($request);

        $validated = $request->validate([
            'orders' => ['required', 'array', 'min:1'],
            'orders.*.items' => ['required', 'array', 'min:1'],
            'orders.*.items.*.product_id' => ['required', 'integer'],
            'orders.*.items.*.quantity' => ['required', 'integer', 'min:1'],
            'orders.*.items.*.variants' => ['sometimes', 'array'],
            'orders.*.items.*.variants.*.option_item_id' => ['required', 'integer', Rule::exists('option_items', 'id')],
            'orders.*.items.*.addons' => ['sometimes', 'array'],
            'orders.*.items.*.addons.*.option_item_id' => ['required', 'integer', Rule::exists('option_items', 'id')],
            'orders.*.items.*.addons.*.quantity' => ['sometimes', 'integer', 'min:1'],
            'orders.*.items.*.preferences' => ['sometimes', 'array'],
            'orders.*.items.*.preferences.*.option_item_id' => ['required', 'integer', Rule::exists('option_items', 'id')],
            'orders.*.items.*.preferences.*.quantity' => ['sometimes', 'integer', 'min:1'],
            'orders.*.items.*.discount_id' => ['nullable', 'integer', Rule::exists('discounts', 'id')],
            'orders.*.items.*.discount' => ['nullable', 'array'],
            'orders.*.items.*.discount.id' => ['nullable', 'integer', Rule::exists('discounts', 'id')],
            'orders.*.items.*.discount.type' => ['nullable', Rule::in(['percentage', 'fixed'])],
            'orders.*.items.*.discount.value' => ['nullable', 'numeric', 'min:0'],
            'orders.*.payment_method' => ['nullable', 'string'],
            'orders.*.nominal_bayar' => ['nullable', 'numeric'],
            'orders.*.transaction_number' => ['nullable', 'string'],
            'orders.*.outlet_id' => ['nullable', 'integer', Rule::exists('outlets', 'id')],
        ]);

        $user = $request->user();
        $userId = $user?->id;
        $defaultOutletId = $this->resolveAccessibleOutletId($request, true);

        $createdOrders = [];

        DB::beginTransaction();

        try {
            foreach ($validated['orders'] as $orderIndex => $orderData) {
                $outletId = (int) ($orderData['outlet_id'] ?? $defaultOutletId);
                $this->assertUserHasOutletAccess($user, $outletId);
                OutletScope::setActiveOutletId($outletId);

                $preparedItems = $this->prepareOrderItems($orderData['items'], $outletId, "orders.$orderIndex.items");
                $totals = $this->calculateOrderTotals($preparedItems);

                $order = Order::create([
                    'user_id' => $userId,
                    'outlet_id' => $outletId,
                    'transaction_number' => $orderData['transaction_number'] ?? 'TRX-' . strtoupper(uniqid()),
                    'transaction_time' => now(),
                    'total_price' => $totals['total_price'],
                    'total_item' => $totals['total_quantity'],
                    'sub_total' => $totals['subtotal'],
                    'discount' => $totals['total_discount'],
                    'discount_amount' => $totals['total_discount'],
                    'payment_method' => $orderData['payment_method'] ?? 'cash',
                    'nominal_bayar' => $orderData['nominal_bayar'] ?? 0,
                    'sync_status' => 'synced',
                    'last_synced' => now(),
                    'client_version' => $orderData['client_version'] ?? 'mobile',
                    'version_id' => 1,
                ]);

                foreach ($preparedItems as $payload) {
                    $orderItem = $order->orderItems()->create([
                        'product_id' => $payload['product']->id,
                        'quantity' => $payload['quantity'],
                        'unit_price_before_discount' => $payload['unit_price_before_discount'],
                        'unit_price_after_discount' => $payload['unit_price_after_discount'],
                        'discount_amount' => $payload['discount_amount'],
                        'applied_discount_type' => $payload['applied_discount_type'],
                        'applied_discount_value' => $payload['applied_discount_value'],
                        'applied_discount_id' => $payload['applied_discount_id'],
                        'total_price' => $payload['total_price'],
                        'outlet_id' => $outletId,
                    ]);

                    $this->persistOrderItemOptions($orderItem, $payload, $order->user_id, $order->outlet_id);
                }

                event(new OrderPaid($order->id));

                $createdOrders[] = $this->transformOrder($order->fresh()->load($this->orderIncludes()));
            }

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            throw ValidationException::withMessages([
                'orders' => __('Gagal membuat order sinkronisasi: :message', ['message' => $e->getMessage()]),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk orders created successfully',
            'data' => $createdOrders,
        ], 201);
    }

    public function refund(Request $request, $id): JsonResponse
    {
        $userId = auth()->id();
        $outletId = $this->resolveAccessibleOutletId($request, true);
        OutletScope::setActiveOutletId($outletId);

        $order = Order::where('user_id', $userId)->find($id);
        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $order->status = 'refund';
        $order->refund_method = $request->input('method');
        $order->refund_nominal = $request->input('nominal');
        $order->sync_status = 'pending';
        $order->last_synced = null;
        $order->version_id = (int) $order->version_id + 1;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated to refund.',
            'order' => $this->transformOrder($order->load($this->orderIncludes())),
        ]);
    }

    protected function prepareOrderItems(array $items, int $outletId, string $pathPrefix): array
    {
        return collect($items)
            ->values()
            ->map(fn (array $item, int $index) => $this->prepareSingleOrderItem($item, $outletId, $pathPrefix . '.' . $index))
            ->all();
    }

    protected function prepareSingleOrderItem(array $item, int $outletId, string $path): array
    {
        $productId = (int) Arr::get($item, 'product_id');
        $quantity = (int) Arr::get($item, 'quantity', 0);

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                "$path.quantity" => __('Jumlah item minimal 1.'),
            ]);
        }

        $product = $this->resolveProduct($productId, $path);

        [$variantRecords, $variantPrice] = $this->resolveVariantSelections(
            $product,
            Arr::get($item, 'variants', []),
            $quantity,
            $path
        );

        [$addonRecords, $addonAdjustment] = $this->resolveAddonSelections(
            $product,
            Arr::get($item, 'addons', []),
            $quantity,
            $path
        );

        [$preferenceRecords, $preferenceAdjustment] = $this->resolvePreferenceSelections(
            $product,
            Arr::get($item, 'preferences', []),
            $quantity,
            $path
        );

        $unitPrice = $variantPrice + $addonAdjustment;
        $discountSummary = $this->resolveItemDiscount(
            $product,
            $item,
            $unitPrice,
            $quantity,
            $outletId,
            $path
        );

        $unitPriceAfterDiscount = max(0, $unitPrice - $discountSummary['unit_discount']);
        $totalPrice = $unitPriceAfterDiscount * $quantity;

        return [
            'product' => $product,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'unit_price_before_discount' => $unitPrice,
            'unit_price_after_discount' => $unitPriceAfterDiscount,
            'discount_amount' => $discountSummary['total_discount'],
            'applied_discount_type' => $discountSummary['type'],
            'applied_discount_value' => $discountSummary['value'],
            'applied_discount_id' => $discountSummary['discount']?->id,
            'total_price' => $totalPrice,
            'variant_records' => $variantRecords,
            'addon_records' => $addonRecords,
            'preference_records' => $preferenceRecords,
            'modifier_records' => $preferenceRecords,
        ];
    }

    protected function resolveProduct(int $productId, string $path): Product
    {
        $product = Product::with([
            'optionGroups.optionGroup',
            'optionGroups.optionItems.optionItem',
        ])->find($productId);

        if (! $product) {
            throw ValidationException::withMessages([
                "$path.product_id" => __('Produk tidak ditemukan atau tidak tersedia untuk outlet ini.'),
            ]);
        }

        return $product;
    }

    protected function resolveVariantSelections(Product $product, array $inputs, int $quantity, string $path): array
    {
        $records = [];
        $resolvedVariantPrice = null;

        $variantGroups = $product->optionGroups
            ->filter(fn ($group) => $group->optionGroup?->type === 'variant');

        if ($variantGroups->isEmpty()) {
            return [[], (int) $product->price];
        }

        $optionItemMap = [];
        foreach ($variantGroups as $group) {
            foreach ($group->optionItems as $pivotItem) {
                $optionItem = $pivotItem->optionItem;
                if (! $optionItem || ! $pivotItem->resolvedIsActive()) {
                    continue;
                }
                $optionItemMap[$optionItem->id] = [
                    'group' => $group,
                    'pivot_item' => $pivotItem,
                    'option_item' => $optionItem,
                ];
            }
        }

        $selectionsByGroup = [];
        $seenItems = [];

        foreach ($inputs as $index => $selection) {
            $optionItemId = Arr::get($selection, 'option_item_id');
            if (! $optionItemId) {
                throw ValidationException::withMessages([
                    "$path.variants.$index.option_item_id" => __('Varian harus dipilih.'),
                ]);
            }

            if (isset($seenItems[$optionItemId])) {
                throw ValidationException::withMessages([
                    "$path.variants.$index.option_item_id" => __('Varian tidak boleh dipilih lebih dari satu kali.'),
                ]);
            }

            $entry = $optionItemMap[$optionItemId] ?? null;
            if (! $entry) {
                throw ValidationException::withMessages([
                    "$path.variants.$index.option_item_id" => __('Varian tidak valid untuk produk ini.'),
                ]);
            }

            $pivotItem = $entry['pivot_item'];
            $stock = $pivotItem->resolvedStock();
            if ($stock !== null && $stock < $quantity) {
                throw ValidationException::withMessages([
                    "$path.variants.$index.option_item_id" => __('Stok varian :variant tidak mencukupi.', ['variant' => $entry['option_item']->name]),
                ]);
            }

            $groupId = $entry['group']->id;
            $selectionsByGroup[$groupId][] = $entry;
            $seenItems[$optionItemId] = true;
        }

        foreach ($variantGroups as $group) {
            $selected = $selectionsByGroup[$group->id] ?? [];
            $selectedCount = count($selected);

            $minRequired = $group->resolvedMinSelect();
            $maxAllowed = $group->resolvedMaxSelect();

            if ($minRequired > 0 && $selectedCount < $minRequired) {
                throw ValidationException::withMessages([
                    "$path.variants" => __('Varian :name wajib dipilih minimal :min item.', [
                        'name' => $group->optionGroup?->name,
                        'min' => $minRequired,
                    ]),
                ]);
            }

            if ($maxAllowed !== null && $selectedCount > $maxAllowed) {
                throw ValidationException::withMessages([
                    "$path.variants" => __('Varian :name maksimal :max pilihan.', [
                        'name' => $group->optionGroup?->name,
                        'max' => $maxAllowed,
                    ]),
                ]);
            }

            if ($group->resolvedSelectionType() === 'single' && $selectedCount > 1) {
                throw ValidationException::withMessages([
                    "$path.variants" => __('Varian :name hanya boleh satu.', [
                        'name' => $group->optionGroup?->name,
                    ]),
                ]);
            }

            foreach ($selected as $entry) {
                if ($resolvedVariantPrice !== null) {
                    throw ValidationException::withMessages([
                        "$path.variants" => __('Setiap produk hanya bisa memiliki satu varian.'),
                    ]);
                }

                $records[] = [
                    'group' => $group,
                    'pivot_item' => $entry['pivot_item'],
                    'option_item' => $entry['option_item'],
                ];
                $resolvedVariantPrice = (int) $product->price + $entry['pivot_item']->resolvedPriceAdjustment();
            }
        }

        if ($resolvedVariantPrice === null) {
            throw ValidationException::withMessages([
                "$path.variants" => __('Varian produk wajib dipilih.'),
            ]);
        }

        return [$records, $resolvedVariantPrice];
    }

    protected function resolvePreferenceSelections(Product $product, array $inputs, int $quantity, string $path): array
    {
        $records = [];

        $preferenceGroups = $product->optionGroups
            ->filter(fn ($group) => $group->optionGroup?->type === 'preference');

        $optionItemMap = [];
        foreach ($preferenceGroups as $group) {
            foreach ($group->optionItems as $pivotItem) {
                $optionItem = $pivotItem->optionItem;
                if (! $optionItem || ! $pivotItem->resolvedIsActive()) {
                    continue;
                }
                $optionItemMap[$optionItem->id] = [
                    'group' => $group,
                    'pivot_item' => $pivotItem,
                    'option_item' => $optionItem,
                ];
            }
        }

        $selectionsByGroup = [];
        $seenItems = [];

        foreach ($inputs as $index => $selection) {
            $optionItemId = Arr::get($selection, 'option_item_id');
            if (! $optionItemId) {
                throw ValidationException::withMessages([
                    "$path.preferences.$index.option_item_id" => __('Preference harus dipilih.'),
                ]);
            }

            if (isset($seenItems[$optionItemId])) {
                throw ValidationException::withMessages([
                    "$path.preferences.$index.option_item_id" => __('Preference tidak boleh dipilih lebih dari satu kali.'),
                ]);
            }

            $entry = $optionItemMap[$optionItemId] ?? null;
            if (! $entry) {
                throw ValidationException::withMessages([
                    "$path.preferences.$index.option_item_id" => __('Preference tidak valid untuk produk ini.'),
                ]);
            }

            $preferenceQty = (int) Arr::get($selection, 'quantity', 1);
            if ($preferenceQty < 1) {
                throw ValidationException::withMessages([
                    "$path.preferences.$index.quantity" => __('Jumlah preference minimal 1.'),
                ]);
            }

            $maxQuantity = $entry['pivot_item']->resolvedMaxQuantity();
            if ($maxQuantity && $preferenceQty > $maxQuantity) {
                throw ValidationException::withMessages([
                    "$path.preferences.$index.quantity" => __('Jumlah preference :name maksimal :max.', [
                        'name' => $entry['option_item']->name,
                        'max' => $maxQuantity,
                    ]),
                ]);
            }

            $groupId = $entry['group']->id;
            $selectionsByGroup[$groupId][] = [
                'entry' => $entry,
                'quantity' => $preferenceQty,
            ];

            $seenItems[$optionItemId] = true;
        }

        foreach ($preferenceGroups as $group) {
            $selected = $selectionsByGroup[$group->id] ?? [];
            $selectedCount = count($selected);

            $minRequired = $group->resolvedMinSelect();
            $maxAllowed = $group->resolvedMaxSelect();

            if ($minRequired > 0 && $selectedCount < $minRequired) {
                throw ValidationException::withMessages([
                    "$path.preferences" => __('Preference :name wajib dipilih minimal :min.', [
                        'name' => $group->optionGroup?->name,
                        'min' => $minRequired,
                    ]),
                ]);
            }

            if ($maxAllowed !== null && $selectedCount > $maxAllowed) {
                throw ValidationException::withMessages([
                    "$path.preferences" => __('Preference :name maksimal :max pilihan.', [
                        'name' => $group->optionGroup?->name,
                        'max' => $maxAllowed,
                    ]),
                ]);
            }

            if ($group->resolvedSelectionType() === 'single' && $selectedCount > 1) {
                throw ValidationException::withMessages([
                    "$path.preferences" => __('Preference :name hanya boleh satu.', [
                        'name' => $group->optionGroup?->name,
                    ]),
                ]);
            }

            foreach ($selected as $selection) {
                $entry = $selection['entry'];
                $qty = $selection['quantity'];
                $records[] = [
                    'group' => $group,
                    'pivot_item' => $entry['pivot_item'],
                    'option_item' => $entry['option_item'],
                    'quantity' => $qty,
                ];
                // Preferences are informational only and must not affect pricing.
            }
        }

        return [$records, 0];
    }

    protected function resolveAddonSelections(Product $product, array $inputs, int $quantity, string $path): array
    {
        $records = [];
        $adjustment = 0;

        $addonGroups = $product->optionGroups
            ->filter(fn ($group) => $group->optionGroup?->type === 'addon');

        $optionItemMap = [];
        foreach ($addonGroups as $group) {
            foreach ($group->optionItems as $pivotItem) {
                $optionItem = $pivotItem->optionItem;
                if (! $optionItem || ! $pivotItem->resolvedIsActive()) {
                    continue;
                }
                $optionItemMap[$optionItem->id] = [
                    'group' => $group,
                    'pivot_item' => $pivotItem,
                    'option_item' => $optionItem,
                ];
            }
        }

        $selectionsByGroup = [];
        $seenItems = [];

        foreach ($inputs as $index => $selection) {
            $optionItemId = Arr::get($selection, 'option_item_id');
            if (! $optionItemId) {
                throw ValidationException::withMessages([
                    "$path.addons.$index.option_item_id" => __('Addon harus dipilih.'),
                ]);
            }

            if (isset($seenItems[$optionItemId])) {
                throw ValidationException::withMessages([
                    "$path.addons.$index.option_item_id" => __('Addon tidak boleh dipilih lebih dari satu kali.'),
                ]);
            }

            $entry = $optionItemMap[$optionItemId] ?? null;
            if (! $entry) {
                throw ValidationException::withMessages([
                    "$path.addons.$index.option_item_id" => __('Addon tidak valid untuk produk ini.'),
                ]);
            }

            $addonQty = (int) Arr::get($selection, 'quantity', 1);
            if ($addonQty < 1) {
                throw ValidationException::withMessages([
                    "$path.addons.$index.quantity" => __('Jumlah addon minimal 1.'),
                ]);
            }

            $maxQuantity = $entry['pivot_item']->resolvedMaxQuantity();
            if ($maxQuantity && $addonQty > $maxQuantity) {
                throw ValidationException::withMessages([
                    "$path.addons.$index.quantity" => __('Jumlah addon :name maksimal :max.', [
                        'name' => $entry['option_item']->name,
                        'max' => $maxQuantity,
                    ]),
                ]);
            }

            $groupId = $entry['group']->id;
            $selectionsByGroup[$groupId][] = [
                'entry' => $entry,
                'quantity' => $addonQty,
            ];

            $seenItems[$optionItemId] = true;
        }

        foreach ($addonGroups as $group) {
            $selected = $selectionsByGroup[$group->id] ?? [];
            $selectedCount = count($selected);

            $minRequired = $group->resolvedMinSelect();
            $maxAllowed = $group->resolvedMaxSelect();

            if ($minRequired > 0 && $selectedCount < $minRequired) {
                throw ValidationException::withMessages([
                    "$path.addons" => __('Addon :name wajib dipilih minimal :min.', [
                        'name' => $group->optionGroup?->name,
                        'min' => $minRequired,
                    ]),
                ]);
            }

            if ($maxAllowed !== null && $selectedCount > $maxAllowed) {
                throw ValidationException::withMessages([
                    "$path.addons" => __('Addon :name maksimal :max pilihan.', [
                        'name' => $group->optionGroup?->name,
                        'max' => $maxAllowed,
                    ]),
                ]);
            }

            foreach ($selected as $selection) {
                $entry = $selection['entry'];
                $qty = $selection['quantity'];
                $records[] = [
                    'group' => $group,
                    'pivot_item' => $entry['pivot_item'],
                    'option_item' => $entry['option_item'],
                    'quantity' => $qty,
                ];
                $adjustment += $entry['pivot_item']->resolvedPriceAdjustment() * $qty;
            }
        }

        return [$records, $adjustment];
    }

    protected function resolveItemDiscount(
        Product $product,
        array $item,
        int $unitPrice,
        int $quantity,
        int $outletId,
        string $path
    ): array {
        $discountId = Arr::get($item, 'discount_id') ?? Arr::get($item, 'discount.id');
        $requestedType = Arr::get($item, 'discount.type');
        $requestedValue = Arr::get($item, 'discount.value');

        $rule = null;
        $discount = null;

        if ($discountId) {
            $rule = DiscountProductRule::with('discount')
                ->where('product_id', $product->id)
                ->where('discount_id', (int) $discountId)
                ->first();

            if (! $rule || ! $rule->discount) {
                throw ValidationException::withMessages([
                    "$path.discount_id" => __('Diskon tidak valid untuk produk ini.'),
                ]);
            }

            $discount = $rule->discount;
        } else {
            $rule = $product->activeDiscountRule();
            $discount = $rule?->discount;

            $autoApply = ($rule?->auto_apply ?? false) || ($discount?->auto_apply ?? false);
            if (! $autoApply) {
                return [
                    'discount' => null,
                    'rule' => null,
                    'type' => null,
                    'value' => null,
                    'unit_discount' => 0,
                    'total_discount' => 0,
                ];
            }
        }

        if (! $rule || ! $discount) {
            return [
                'discount' => null,
                'rule' => null,
                'type' => null,
                'value' => null,
                'unit_discount' => 0,
                'total_discount' => 0,
            ];
        }

        $this->assertDiscountApplicability($rule, $discount, $product, $outletId, $path);

        $type = $rule->type_override ?? $discount->type;
        $value = $rule->value_override ?? $discount->value;

        if ($requestedType && $requestedType !== $type) {
            throw ValidationException::withMessages([
                "$path.discount.type" => __('Tipe diskon tidak sesuai.'),
            ]);
        }

        if ($requestedValue !== null && (float) $requestedValue != (float) $value) {
            throw ValidationException::withMessages([
                "$path.discount.value" => __('Nilai diskon tidak sesuai.'),
            ]);
        }

        $unitDiscount = $this->calculateUnitDiscount($unitPrice, $type, $value);
        $totalDiscount = min($unitDiscount * max(1, $quantity), $unitPrice * max(1, $quantity));

        return [
            'discount' => $discount,
            'rule' => $rule,
            'type' => $type,
            'value' => $value,
            'unit_discount' => $unitDiscount,
            'total_discount' => $totalDiscount,
        ];
    }

    protected function assertDiscountApplicability(
        DiscountProductRule $rule,
        Discount $discount,
        Product $product,
        int $outletId,
        string $path
    ): void {
        if ($discount->status !== 'active') {
            throw ValidationException::withMessages([
                "$path.discount_id" => __('Diskon tidak aktif.'),
            ]);
        }

        $now = Carbon::now();

        if ($discount->expired_date && $discount->expired_date->endOfDay() < $now) {
            throw ValidationException::withMessages([
                "$path.discount_id" => __('Diskon sudah kadaluarsa.'),
            ]);
        }

        if ($rule->valid_from && $rule->valid_from->gt($now)) {
            throw ValidationException::withMessages([
                "$path.discount_id" => __('Diskon belum berlaku.'),
            ]);
        }

        if ($rule->valid_until && $rule->valid_until->lt($now)) {
            throw ValidationException::withMessages([
                "$path.discount_id" => __('Diskon sudah tidak berlaku.'),
            ]);
        }

        if ($rule->outlet_id && $rule->outlet_id !== $outletId) {
            throw ValidationException::withMessages([
                "$path.discount_id" => __('Diskon tidak tersedia untuk outlet ini.'),
            ]);
        }

        if ($discount->outlet_id && $discount->outlet_id !== $outletId) {
            throw ValidationException::withMessages([
                "$path.discount_id" => __('Diskon tidak tersedia untuk outlet ini.'),
            ]);
        }

        if ($product->outlet_id && $discount->scope === 'outlet' && $discount->outlet_id && $product->outlet_id !== $discount->outlet_id) {
            throw ValidationException::withMessages([
                "$path.discount_id" => __('Diskon tidak sesuai dengan outlet produk.'),
            ]);
        }
    }

    protected function calculateUnitDiscount(int $unitPrice, ?string $type, $rawValue): int
    {
        if ($unitPrice <= 0 || ! $type) {
            return 0;
        }

        $value = is_numeric($rawValue)
            ? (float) $rawValue
            : (float) str_replace(',', '.', (string) $rawValue);

        if ($value <= 0) {
            return 0;
        }

        if ($type === 'percentage') {
            $percent = min(100, max(0, $value));
            return (int) floor($unitPrice * ($percent / 100));
        }

        if ($type === 'fixed') {
            return (int) min($unitPrice, round($value));
        }

        return 0;
    }

    protected function persistOrderItemOptions($orderItem, array $payload, int $userId, int $outletId): void
    {
        foreach ($payload['variant_records'] as $record) {
            $group = $record['group'];
            $pivotItem = $record['pivot_item'];
            $optionItem = $record['option_item'];

            $orderItem->variantSelections()->create([
                'product_variant_id' => null,
                'option_item_id' => $optionItem->id,
                'user_id' => $userId,
                'outlet_id' => $outletId,
                'variant_group_name' => $group->optionGroup?->name,
                'variant_name' => $optionItem->name,
                'price_adjustment' => $pivotItem->resolvedPriceAdjustment(),
            ]);

            $this->consumeOptionItemStock($optionItem->id, $payload['quantity']);
        }

        foreach ($payload['addon_records'] as $record) {
            $group = $record['group'];
            $pivotItem = $record['pivot_item'];
            $optionItem = $record['option_item'];

            $orderItem->addonSelections()->create([
                'product_addon_id' => null,
                'option_item_id' => $optionItem->id,
                'user_id' => $userId,
                'outlet_id' => $outletId,
                'addon_group_name' => $group->optionGroup?->name,
                'addon_name' => $optionItem->name,
                'price_adjustment' => $pivotItem->resolvedPriceAdjustment(),
                'quantity' => (int) $record['quantity'],
            ]);

            $this->consumeOptionItemStock(
                $optionItem->id,
                $payload['quantity'] * (int) $record['quantity']
            );
        }

        $preferenceRecords = $payload['preference_records'] ?? $payload['modifier_records'] ?? [];

        foreach ($preferenceRecords as $record) {
            $group = $record['group'];
            $pivotItem = $record['pivot_item'];
            $optionItem = $record['option_item'];

            $orderItem->preferenceSelections()->create([
                'option_item_id' => $optionItem->id,
                'user_id' => $userId,
                'outlet_id' => $outletId,
                'preference_group_name' => $group->optionGroup?->name,
                'preference_name' => $optionItem->name,
                'price_adjustment' => $pivotItem->resolvedPriceAdjustment(),
                'quantity' => (int) $record['quantity'],
            ]);
        }
    }

    protected function consumeOptionItemStock(int $optionItemId, int $quantity): void
    {
        $optionItem = OptionItem::lockForUpdate()->find($optionItemId);
        if (! $optionItem || $optionItem->stock === null) {
            return;
        }

        if ($optionItem->stock < $quantity) {
            throw ValidationException::withMessages([
                'items' => __('Stok varian :variant tidak mencukupi.', ['variant' => $optionItem->name]),
            ]);
        }

        $optionItem->stock -= $quantity;
        $optionItem->sync_status = 'pending';
        $optionItem->last_synced = null;
        $optionItem->version_id = (int) $optionItem->version_id + 1;
        $optionItem->save();
    }

    protected function calculateOrderTotals(array $preparedItems): array
    {
        $totalPrice = 0;
        $totalQuantity = 0;
        $subtotal = 0;
        $totalDiscount = 0;

        foreach ($preparedItems as $payload) {
            $lineQuantity = $payload['quantity'];
            $totalPrice += $payload['total_price'];
            $totalQuantity += $lineQuantity;
            $subtotal += ($payload['unit_price_before_discount'] ?? $payload['unit_price']) * $lineQuantity;
            $totalDiscount += $payload['discount_amount'] ?? 0;
        }

        return [
            'total_price' => $totalPrice,
            'total_quantity' => $totalQuantity,
            'subtotal' => $subtotal,
            'total_discount' => $totalDiscount,
        ];
    }

    protected function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'transaction_number' => $order->transaction_number,
            'user_id' => $order->user_id,
            'total_price' => $order->total_price,
            'total_item' => $order->total_item,
            'sub_total' => $order->sub_total,
            'discount' => $order->discount,
            'discount_amount' => $order->discount_amount,
            'payment_method' => $order->payment_method,
            'created_at' => optional($order->created_at)->copy()->setTimezone('UTC')->toIso8601String(),
            'updated_at' => optional($order->updated_at)->copy()->setTimezone('UTC')->toIso8601String(),
            'status' => $order->status,
            'order_items' => $order->orderItems->map(function ($item) {
                $unitPriceAfter = $item->unit_price_after_discount ?? ($item->quantity > 0
                    ? (int) round($item->total_price / $item->quantity)
                    : (int) $item->total_price);
                $unitPriceBefore = $item->unit_price_before_discount ?? $unitPriceAfter;
                $lineDiscount = $item->discount_amount ?? max(0, ($unitPriceBefore - $unitPriceAfter) * max(1, $item->quantity));
                $unitDiscount = $item->quantity > 0
                    ? (int) floor($lineDiscount / $item->quantity)
                    : 0;

                $primaryVariant = $item->variantSelections->first();
                $primaryOptionItem = $primaryVariant?->optionItem;
                $primaryBasePrice = $primaryOptionItem?->price_adjustment ?? 0;
                $primaryResolvedPrice = $primaryVariant?->price_adjustment ?? 0;

                return [
                    'id' => $item->id,
                    'order_id' => $item->order_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'total_price' => $item->total_price,
                    'unit_price' => $unitPriceAfter,
                    'unit_price_before_discount' => $unitPriceBefore,
                    'unit_price_after_discount' => $unitPriceAfter,
                    'discount_amount' => $lineDiscount,
                    'discount_per_unit' => $unitDiscount,
                    'applied_discount_id' => $item->applied_discount_id,
                    'applied_discount_type' => $item->applied_discount_type,
                    'applied_discount_value' => $item->applied_discount_value,
                    'applied_discount' => $item->appliedDiscount ? [
                        'id' => $item->appliedDiscount->id,
                        'name' => $item->appliedDiscount->name,
                        'type' => $item->applied_discount_type ?? $item->appliedDiscount->type,
                        'value' => $item->applied_discount_value ?? $item->appliedDiscount->value,
                        'auto_apply' => $item->appliedDiscount->auto_apply,
                    ] : null,
                    'created_at' => optional($item->created_at)->copy()->setTimezone('UTC')->toIso8601String(),
                    'updated_at' => optional($item->updated_at)->copy()->setTimezone('UTC')->toIso8601String(),
                    'product' => $item->product,
                    'variant' => $primaryVariant ? [
                        'id' => $primaryVariant->option_item_id ?? $primaryVariant->id,
                        'name' => $primaryVariant->variant_name,
                        'price' => $primaryResolvedPrice,
                        'price_adjustment' => $primaryBasePrice,
                        'base_price_adjustment' => $primaryBasePrice,
                        'resolved_price_adjustment' => $primaryResolvedPrice,
                        'price_adjustment_override' => $primaryResolvedPrice !== $primaryBasePrice
                            ? $primaryResolvedPrice
                            : null,
                        'option_item_id' => $primaryVariant->option_item_id,
                    ] : null,
                    'variants' => $item->variantSelections->map(function ($variant) {
                        $optionItem = $variant->optionItem;
                        $base = $optionItem?->price_adjustment ?? 0;
                        $resolved = $variant->price_adjustment ?? 0;

                        return [
                            'id' => $variant->id,
                            'product_variant_id' => $variant->product_variant_id,
                            'option_item_id' => $variant->option_item_id,
                            'name' => $variant->variant_name,
                            'variant_group_name' => $variant->variant_group_name,
                            'variant_name' => $variant->variant_name,
                            'price_adjustment' => $base,
                            'base_price_adjustment' => $base,
                            'resolved_price_adjustment' => $resolved,
                            'price_adjustment_override' => $resolved !== $base ? $resolved : null,
                            'option_item' => $variant->optionItem,
                        ];
                    }),
                    'addons' => $item->addonSelections->map(function ($addon) {
                        $optionItem = $addon->optionItem;
                        $base = $optionItem?->price_adjustment ?? 0;
                        $resolved = $addon->price_adjustment ?? 0;

                        return [
                            'id' => $addon->id,
                            'product_addon_id' => $addon->product_addon_id,
                            'option_item_id' => $addon->option_item_id,
                            'name' => $addon->addon_name,
                            'addon_group_name' => $addon->addon_group_name,
                            'addon_name' => $addon->addon_name,
                            'price_adjustment' => $base,
                            'base_price_adjustment' => $base,
                            'resolved_price_adjustment' => $resolved,
                            'price_adjustment_override' => $resolved !== $base ? $resolved : null,
                            'quantity' => $addon->quantity,
                            'option_item' => $addon->optionItem,
                        ];
                    }),
                    'preferences' => $item->preferenceSelections->map(function ($preference) {
                        $optionItem = $preference->optionItem;
                        $base = $optionItem?->price_adjustment ?? 0;
                        $resolved = $preference->price_adjustment ?? 0;

                        return [
                            'id' => $preference->id,
                            'option_item_id' => $preference->option_item_id,
                            'name' => $preference->preference_name,
                            'preference_group_name' => $preference->preference_group_name,
                            'preference_name' => $preference->preference_name,
                            'price_adjustment' => $base,
                            'base_price_adjustment' => $base,
                            'resolved_price_adjustment' => $resolved,
                            'price_adjustment_override' => $resolved !== $base ? $resolved : null,
                            'quantity' => $preference->quantity,
                            'option_item' => $preference->optionItem,
                        ];
                    }),
                    'modifiers' => $item->preferenceSelections->map(function ($preference) {
                        $optionItem = $preference->optionItem;
                        $base = $optionItem?->price_adjustment ?? 0;
                        $resolved = $preference->price_adjustment ?? 0;

                        return [
                            'id' => $preference->id,
                            'option_item_id' => $preference->option_item_id,
                            'name' => $preference->preference_name,
                            'modifier_group_name' => $preference->preference_group_name,
                            'modifier_name' => $preference->preference_name,
                            'price_adjustment' => $base,
                            'base_price_adjustment' => $base,
                            'resolved_price_adjustment' => $resolved,
                            'price_adjustment_override' => $resolved !== $base ? $resolved : null,
                            'quantity' => $preference->quantity,
                            'option_item' => $preference->optionItem,
                        ];
                    }),
                ];
            }),
        ];
    }

    protected function normalizePreferenceSelections(Request $request): void
    {
        if ($request->has('items')) {
            $items = $request->input('items');
            if (is_array($items)) {
                $request->merge(['items' => $this->mapPreferenceSelections($items)]);
            }
        }

        if ($request->has('orders')) {
            $orders = $request->input('orders');
            if (is_array($orders)) {
                $orders = array_map(function ($order) {
                    if (isset($order['items']) && is_array($order['items'])) {
                        $order['items'] = $this->mapPreferenceSelections($order['items']);
                    }

                    return $order;
                }, $orders);

                $request->merge(['orders' => $orders]);
            }
        }
    }

    protected function mapPreferenceSelections(array $items): array
    {
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            if (! array_key_exists('preferences', $item) && array_key_exists('modifiers', $item)) {
                $item['preferences'] = $item['modifiers'];
            }

            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = $this->mapPreferenceSelections($item['items']);
            }

            $items[$index] = $item;
        }

        return $items;
    }

    protected function orderIncludes(): array
    {
        return [
            'orderItems.product',
            'orderItems.appliedDiscount',
            'orderItems.variantSelections.optionItem',
            'orderItems.addonSelections.optionItem',
            'orderItems.preferenceSelections.optionItem',
        ];
    }

    protected function resolveAccessibleOutletId(Request $request, bool $require = false): ?int
    {
        $user = $request->user();
        if (! $user) {
            if ($require) {
                throw ValidationException::withMessages([
                    'outlet_id' => __('User tidak terautentikasi.'),
                ]);
            }

            return null;
        }

        $candidate = $request->input('outlet_id');
        if ($candidate) {
            $candidate = (int) $candidate;
        }

        if (! $candidate) {
            $candidate = OutletContext::currentOutlet()?->id;
        }

        if (! $candidate) {
            $candidate = $user->outletRoles()
                ->where('status', 'active')
                ->orderByRaw("role = 'owner' DESC")
                ->orderBy('created_at')
                ->value('outlet_id');
        }

        if (! $candidate) {
            $candidate = $this->provisionDefaultOutlet($user);
        }

        if (! $candidate) {
            if ($require) {
                throw ValidationException::withMessages([
                    'outlet_id' => __('Outlet belum tersedia untuk pengguna ini.'),
                ]);
            }

            return null;
        }

        $this->assertUserHasOutletAccess($user, $candidate);

        return (int) $candidate;
    }

    protected function assertUserHasOutletAccess($user, int $outletId): void
    {
        $hasAccess = $user->outletRoles()
            ->where('outlet_id', $outletId)
            ->where('status', 'active')
            ->exists();

        if (! $hasAccess) {
            abort(403, __('Anda tidak memiliki akses ke outlet tersebut.'));
        }
    }

    protected function provisionDefaultOutlet($user): ?int
    {
        if (! $user) {
            return null;
        }

        $existing = $user->outletRoles()
            ->where('status', 'active')
            ->orderByRaw("role = 'owner' DESC")
            ->orderBy('created_at')
            ->value('outlet_id');

        if ($existing) {
            return (int) $existing;
        }

        $outletName = $user->store_name ?: ($user->name ?: 'Outlet ' . $user->id);

        $outlet = Outlet::create([
            'name' => $outletName,
            'created_by' => $user->id,
        ]);

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

        return $outlet->id;
    }
}
