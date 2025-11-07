<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesOutlet;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionItem;
use App\Models\DiscountProductRule;
use App\Models\Discount;
use App\Services\ProductOptionService;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Throwable;

class ProductController extends Controller
{
    use ResolvesOutlet;

    public function __construct(private ProductOptionService $optionService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $outletId = $this->resolveOutletId($request, true);

        $products = Product::with($this->productIncludes())
            ->where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->orderBy('is_best_seller', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'List Data Product',
            'data' => $this->serializeProducts($products),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $outletId = $this->resolveOutletId($request, true);

        $this->mergePreferenceGroups($request);

        $validator = $this->makeProductValidator($request, $user->id, $outletId);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), 422);
        }

        try {
            $product = DB::transaction(function () use ($request, $user, $outletId) {
                $imageFilename = $this->storeProductImage($request);

                $product = new Product([
                    'user_id' => $user->id,
                    'outlet_id' => $outletId,
                    'name' => $request->string('name')->trim(),
                    'description' => $request->input('description'),
                    'price' => (int) round($request->input('price', 0)),
                    'stock' => (int) $request->input('stock', 0),
                    'category_id' => (int) $request->input('category_id'),
                    'image' => $imageFilename,
                    'sync_status' => 'pending',
                    'last_synced' => null,
                    'version_id' => 1,
                ]);

                $product->save();

                if ($request->has('variant_groups')) {
                    $this->optionService->syncVariantGroups($product, $request->input('variant_groups', []));
                }

                if ($request->has('addon_groups')) {
                    $this->optionService->syncAddonGroups($product, $request->input('addon_groups', []));
                }
                if ($request->has('preference_groups')) {
                    $this->optionService->syncPreferenceGroups($product, $request->input('preference_groups', []));
                }

                return $product->fresh()->load($this->productIncludes());
            });

            return response()->json([
                'success' => true,
                'message' => 'Product Created',
                'data' => $this->serializeProduct($product),
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product.',
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $outletId = $this->resolveOutletId($request, true);

        $product = Product::where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->findOrFail($request->input('id'));

        $this->mergePreferenceGroups($request);

        $validator = $this->makeProductValidator($request, $user->id, $outletId, $product);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), 422);
        }

        try {
            $product = DB::transaction(function () use ($request, $product) {
                $this->updateProductAttributes($product, $request);
                $product->sync_status = 'pending';
                $product->version_id = (int) $product->version_id + 1;
                $product->last_synced = null;
                $product->save();

                if ($request->has('variant_groups')) {
                    $this->optionService->syncVariantGroups($product, $request->input('variant_groups', []));
                }

                if ($request->has('addon_groups')) {
                    $this->optionService->syncAddonGroups($product, $request->input('addon_groups', []));
                }
                if ($request->has('preference_groups')) {
                    $this->optionService->syncPreferenceGroups($product, $request->input('preference_groups', []));
                }

                return $product->fresh()->load($this->productIncludes());
            });

            return response()->json([
                'success' => true,
                'message' => 'Product Updated',
                'data' => $this->serializeProduct($product),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product.',
            ], 500);
        }
    }

    public function getByCategory(Request $request, $categoryId): JsonResponse
    {
        $user = $request->user();
        $outletId = $this->resolveOutletId($request, true);

        $categoryExists = Category::where('id', $categoryId)
            ->where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->exists();

        if (! $categoryExists) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        $products = Product::with($this->productIncludes())
            ->where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->where('category_id', $categoryId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $this->serializeProducts($products),
        ]);
    }

    public function getWithStock(Request $request): JsonResponse
    {
        $user = $request->user();
        $outletId = $this->resolveOutletId($request, true);

        $products = Product::with($this->productIncludes())
            ->where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Products with stock retrieved successfully',
            'data' => $this->serializeProducts($products),
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $outletId = $this->resolveOutletId($request, true);

        $product = Product::where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->findOrFail($id);

        if (method_exists($product, 'orderItems') && $product->orderItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak bisa dihapus karena sudah memiliki transaksi.',
            ], 409);
        }

        $publicPath = public_path('products/' . $product->image);
        if ($product->image && File::exists($publicPath)) {
            File::delete($publicPath);
        }

        try {
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product Deleted',
            ]);
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak bisa dihapus karena sudah memiliki transaksi.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus produk.',
            ], 500);
        }
    }

    protected function makeProductValidator(Request $request, int $userId, int $outletId, ?Product $product = null): ValidatorContract
    {
        $productId = $product?->id;

        $baseRules = [
            'id' => $product ? [
                'required', 'integer',
                Rule::exists('products', 'id')->where(fn ($q) => $q
                    ->where('user_id', $userId)
                    ->where('outlet_id', $outletId)
                    ->whereNull('deleted_at')),
            ] : ['prohibited'],
            'name' => [
                'required', 'string', 'min:3', 'max:255',
                Rule::unique('products', 'name')
                    ->where(fn ($q) => $q
                        ->where('user_id', $userId)
                        ->where('outlet_id', $outletId)
                        ->whereNull('deleted_at'))
                    ->ignore($productId),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer'],
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q
                    ->where('user_id', $userId)
                    ->where('outlet_id', $outletId)
                    ->whereNull('deleted_at')),
            ],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];

        $rules = array_merge($baseRules, $this->optionService->optionRules($product));

        $validator = Validator::make($request->all(), $rules);
        $this->optionService->attachIntegrityChecks($validator, $request->all(), $product);

        return $validator;
    }

    protected function updateProductAttributes(Product $product, Request $request): void
    {
        $product->name = $request->string('name')->trim();
        $product->description = $request->input('description');
        $product->price = (int) round($request->input('price', 0));
        $product->stock = (int) $request->input('stock', 0);
        $product->category_id = (int) $request->input('category_id');

        $imageFilename = $this->storeProductImage($request, $product->image);
        if ($imageFilename !== $product->image) {
            $product->image = $imageFilename;
        }
    }

    protected function storeProductImage(Request $request, ?string $existingFilename = null): ?string
    {
        if (! $request->hasFile('image')) {
            return $existingFilename;
        }

        $productImagePath = public_path('products');
        if (! file_exists($productImagePath)) {
            mkdir($productImagePath, 0777, true);
        }

        if ($existingFilename && file_exists($productImagePath . '/' . $existingFilename)) {
            File::delete($productImagePath . '/' . $existingFilename);
        }

        $filename = time() . '-' . uniqid('product_') . '.' . $request->file('image')->extension();
        $request->file('image')->move($productImagePath, $filename);

        return $filename;
    }

    protected function serializeProducts(Collection $products): array
    {
        return $products->map(fn (Product $product) => $this->serializeProduct($product))->all();
    }

    protected function serializeProduct(Product $product): array
    {
        $product->loadMissing($this->productIncludes());
        $data = $product->toArray();

        $variantGroups = $product->optionGroups
            ->filter(fn ($group) => $group->optionGroup?->type === 'variant')
            ->values()
            ->map(fn (ProductOptionGroup $group) => $this->mapOptionGroup($group, 'variant'));

        $addonGroups = $product->optionGroups
            ->filter(fn ($group) => $group->optionGroup?->type === 'addon')
            ->values()
            ->map(fn (ProductOptionGroup $group) => $this->mapOptionGroup($group, 'addon'));

        $preferenceGroups = $product->optionGroups
            ->filter(fn ($group) => $group->optionGroup?->type === 'preference')
            ->values()
            ->map(fn (ProductOptionGroup $group) => $this->mapOptionGroup($group, 'preference'));

        $data['variant_groups'] = $variantGroups->map(function ($group) {
            return is_array($group) ? $group : $group->toArray();
        })->values()->all();

        $data['addon_groups'] = $addonGroups->map(function ($group) {
            return is_array($group) ? $group : $group->toArray();
        })->values()->all();

        $preferenceGroupsPayload = $preferenceGroups->map(function ($group) {
            return is_array($group) ? $group : $group->toArray();
        })->values()->all();
        $data['preference_groups'] = $preferenceGroupsPayload;
        $data['modifier_groups'] = $preferenceGroupsPayload; // backward compatibility

        $activeRule = $product->activeDiscountRule();
        $basePrice = (int) ($data['default_price'] ?? $product->default_price ?? $product->price ?? 0);
        $discountAmount = 0;
        $activeDiscountPayload = null;

        if ($activeRule && $activeRule->discount) {
            $discount = $activeRule->discount;
            $type = $activeRule->type_override ?? $discount->type;
            $value = $activeRule->value_override ?? $discount->value;
            $discountAmount = $this->calculateDiscountReduction($basePrice, $type, $value);
            $expiredAt = $discount->expired_date ? $discount->expired_date->copy()->endOfDay() : null;

            $activeDiscountPayload = [
                'id' => $discount->id,
                'rule_id' => $activeRule->id,
                'name' => $discount->name,
                'type' => $type,
                'value' => $value,
                'auto_apply' => ($activeRule->auto_apply ?? false) || ($discount->auto_apply ?? false),
                'priority' => $activeRule->priority ?? $discount->priority,
                'valid_from' => optional($activeRule->valid_from)->toIso8601String(),
                'valid_until' => optional($activeRule->valid_until)->toIso8601String(),
                'expired_date' => $expiredAt?->toIso8601String(),
            ];
        }

        $data['discount_amount'] = $discountAmount;
        $data['discounted_price'] = max(0, $basePrice - $discountAmount);
        $data['active_discount'] = $activeDiscountPayload;
        $data['discount_rules'] = $product->discountRules
            ->map(fn (DiscountProductRule $rule) => $this->mapDiscountRule($rule))
            ->values()
            ->all();

        $data['variants'] = $variantGroups
            ->flatMap(function ($group) {
                $groupArray = is_array($group) ? $group : $group->toArray();

                $itemsKey = $this->itemsKeyForType($groupArray['type'] ?? 'variant');
                $items = $groupArray[$itemsKey] ?? [];

                return collect($items)->map(function ($variant) use ($groupArray) {
                    return [
                        'id' => $variant['id'] ?? null,
                        'group_id' => $groupArray['id'] ?? null,
                        'name' => $variant['name'] ?? null,
                        'price' => $variant['resolved_price_adjustment']
                            ?? $variant['price_adjustment']
                            ?? 0,
                        'price_adjustment' => $variant['price_adjustment'] ?? 0,
                        'base_price_adjustment' => $variant['base_price_adjustment'] ?? $variant['price_adjustment'] ?? 0,
                        // 'base_price_adjustment' => 
                        // (isset($variant['price_adjustment']) && $variant['price_adjustment'] > 0)
                        //     ? $variant['price_adjustment']
                        //     : ($variant['base_price_adjustment'] ?? 0),
                        'price_adjustment_override' => $variant['price_adjustment_override'] ?? null,
                        'option_item_id' => $variant['option_item_id'] ?? null,
                        'resolved_price_adjustment' => $variant['resolved_price_adjustment'] ?? null,
                        'is_default' => $variant['is_default'] ?? false,
                        'sku' => $variant['sku'] ?? null,
                        'max_quantity' => $variant['resolved_max_quantity']
                            ?? $variant['max_quantity']
                            ?? null,
                        'resolved_max_quantity' => $variant['resolved_max_quantity'] ?? null,
                    ];
                });
            })
            ->values()
            ->all();

        $data['addons'] = $addonGroups
            ->flatMap(function ($group) {
                $groupArray = is_array($group) ? $group : $group->toArray();

                $itemsKey = $this->itemsKeyForType($groupArray['type'] ?? 'addon');
                $items = $groupArray[$itemsKey] ?? [];

                return collect($items)->map(function ($addon) use ($groupArray) {
                    $useProductPrice = $addon['use_product_price'] ?? false;
                    return [
                        'id' => $addon['id'] ?? null,
                        'group_id' => $groupArray['id'] ?? null,
                        'name' => $addon['name'] ?? null,
                        'price' => $addon['resolved_price_adjustment']
                            ?? $addon['price_adjustment']
                            ?? 0,
                        'price_adjustment' => $addon['price_adjustment'] ?? 0,
                        'base_price_adjustment' => $addon['base_price_adjustment'] ?? $addon['price_adjustment'] ?? 0,
                        'price_adjustment_override' => $addon['price_adjustment_override'] ?? null,
                        'option_item_id' => $addon['option_item_id'] ?? null,
                        'max_quantity' => $addon['resolved_max_quantity']
                            ?? $addon['max_quantity']
                            ?? null,
                        'resolved_price_adjustment' => $addon['resolved_price_adjustment'] ?? null,
                        'resolved_max_quantity' => $addon['resolved_max_quantity'] ?? null,
                        'is_required' => $groupArray['is_required'] ?? false,
                        'product_id' => $addon['product_id'] ?? null,
                        'product_name' => $addon['product_name'] ?? null,
                        'product_sku' => $addon['product_sku'] ?? null,
                        'product_price' => $addon['product_price'] ?? null,
                        'use_product_price' => (bool) $useProductPrice,
                    ];
                });
            })
            ->values()
            ->all();

        $preferencesPayload = $preferenceGroups
            ->flatMap(function ($group) {
                $groupArray = is_array($group) ? $group : $group->toArray();

                $itemsKey = $this->itemsKeyForType($groupArray['type'] ?? 'preference');
                $items = $groupArray[$itemsKey] ?? [];

                return collect($items)->map(function ($preference) use ($groupArray) {
                    return [
                        'id' => $preference['id'] ?? null,
                        'group_id' => $groupArray['id'] ?? null,
                        'name' => $preference['name'] ?? null,
                        'price' => $preference['resolved_price_adjustment']
                            ?? $preference['price_adjustment']
                            ?? 0,
                        'price_adjustment' => $preference['price_adjustment'] ?? 0,
                        'base_price_adjustment' => $preference['base_price_adjustment'] ?? $preference['price_adjustment'] ?? 0,
                        'price_adjustment_override' => $preference['price_adjustment_override'] ?? null,
                        'option_item_id' => $preference['option_item_id'] ?? null,
                        'max_quantity' => $preference['resolved_max_quantity']
                            ?? $preference['max_quantity']
                            ?? null,
                        'resolved_price_adjustment' => $preference['resolved_price_adjustment'] ?? null,
                        'resolved_max_quantity' => $preference['resolved_max_quantity'] ?? null,
                        'is_required' => $groupArray['is_required'] ?? false,
                        'is_default' => $preference['is_default'] ?? false,
                    ];
                });
            })
            ->values()
            ->all();
        $data['preferences'] = $preferencesPayload;
        $data['modifiers'] = $preferencesPayload; // backward compatibility

        return $data;
    }

    protected function mapOptionGroup(ProductOptionGroup $group, string $type): array
    {
        $optionItems = $group->optionItems
            ->sortBy('sort_order')
            ->values()
            ->map(function (ProductOptionItem $item) {
                $option = $item->optionItem;
                $product = $option?->product;
                $useProductPrice = (bool) ($option?->use_product_price ?? false);
                $productPrice = (int) ($product?->price ?? 0);

                $basePriceAdjustment = $option?->price_adjustment;
                $resolvedPriceAdjustment = $item->resolvedPriceAdjustment();

                if ($useProductPrice) {
                    $basePriceAdjustment = $productPrice;
                    $resolvedPriceAdjustment = $productPrice;
                }

                return [
                    'id' => $item->id,
                    'option_item_id' => $item->option_item_id,
                    'name' => $option?->name,
                    'product_id' => $option?->product_id,
                    'product_name' => $product?->name,
                    'product_sku' => $product?->sku ?? $option?->sku,
                    'product_price' => $productPrice,
                    'price_adjustment' => $basePriceAdjustment,
                    'base_price_adjustment' => $basePriceAdjustment,
                    'price_adjustment_override' => $item->price_adjustment_override,
                    'resolved_price_adjustment' => $resolvedPriceAdjustment,
                    'stock' => $option?->stock,
                    'stock_override' => $item->stock_override,
                    'resolved_stock' => $item->resolvedStock(),
                    'sku' => $option?->sku,
                    'sku_override' => $item->sku_override,
                    'resolved_sku' => $item->resolvedSku(),
                    'max_quantity' => $option?->max_quantity,
                    'max_quantity_override' => $item->max_quantity_override,
                    'resolved_max_quantity' => $item->resolvedMaxQuantity(),
                    'is_default' => $option?->is_default,
                    'is_default_override' => $item->is_default_override,
                    'resolved_is_default' => $item->resolvedIsDefault(),
                    'is_active' => $option?->is_active,
                    'is_active_override' => $item->is_active_override,
                    'resolved_is_active' => $item->resolvedIsActive(),
                    'sort_order' => $item->sort_order,
                    'use_product_price' => $useProductPrice,
                ];
            });

        return [
            'id' => $group->id,
            'option_group_id' => $group->option_group_id,
            'name' => $group->optionGroup?->name,
            'type' => $type,
            'is_required' => $group->resolvedIsRequired(),
            'selection_type' => $group->resolvedSelectionType(),
            'min_select' => $group->resolvedMinSelect(),
            'max_select' => $group->resolvedMaxSelect(),
            'sort_order' => $group->sort_order,
            $this->itemsKeyForType($type) => $optionItems,
        ];
    }

    protected function mapDiscountRule(DiscountProductRule $rule): array
    {
        $discount = $rule->discount;
        $type = $rule->type_override ?? $discount?->type;
        $value = $rule->value_override ?? $discount?->value;
        $expiredAt = $discount?->expired_date ? $discount->expired_date->copy()->endOfDay() : null;

        return [
            'id' => $rule->id,
            'discount_id' => $discount?->id,
            'name' => $discount?->name,
            'type' => $type,
            'value' => $value,
            'auto_apply' => ($rule->auto_apply ?? false) || ($discount?->auto_apply ?? false),
            'priority' => $rule->priority ?? $discount?->priority,
            'valid_from' => optional($rule->valid_from)->toIso8601String(),
            'valid_until' => optional($rule->valid_until)->toIso8601String(),
            'expired_date' => $expiredAt?->toIso8601String(),
            'status' => $discount?->status,
            'scope' => $discount?->scope,
            'outlet_id' => $rule->outlet_id ?? $discount?->outlet_id,
        ];
    }

    protected function calculateDiscountReduction(int $basePrice, ?string $type, $rawValue): int
    {
        if ($basePrice <= 0 || ! $type) {
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
            return (int) floor($basePrice * ($percent / 100));
        }

        if ($type === 'fixed') {
            return (int) min($basePrice, round($value));
        }

        return 0;
    }

    protected function productIncludes(): array
    {
        return [
            'category',
            'optionGroups.optionGroup',
            'optionGroups.optionItems.optionItem',
            'discountRules.discount',
        ];
    }

    protected function itemsKeyForType(string $type): string
    {
        switch ($type) {
            case 'variant':
                return 'variants';
            case 'addon':
                return 'addons';
            case 'preference':
                return 'preferences';
            default:
                return "{$type}_items";
        }
    }

    protected function mergePreferenceGroups(Request $request): void
    {
        if (! $request->has('preference_groups') && $request->has('modifier_groups')) {
            $request->merge([
                'preference_groups' => $request->input('modifier_groups'),
            ]);
        }
    }
}
