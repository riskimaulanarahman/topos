<?php

namespace App\Http\Controllers;

use App\Mail\PartnerProductRequestMail;
use App\Models\Category;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionItem;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Models\RawMaterial;
use App\Services\ProductOptionService;
use App\Services\RecipeService;
use App\Support\OutletContext;
use App\Services\PartnerCategoryAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->resolveOutletContext();
        $ownerUserIds = $context['owner_user_ids'];
        $accessibleCategoryIds = $context['accessible_category_ids'];

        $query = Product::with([
                'category',
                'variantGroups.optionGroup',
                'variantGroups.optionItems',
                'addonGroups.optionGroup',
                'addonGroups.optionItems',
                'preferenceGroups.optionGroup',
                'preferenceGroups.optionItems',
                'discountRules.discount',
            ])
            ->whereIn('user_id', $ownerUserIds)
            ->when($this->shouldFilterCategories($accessibleCategoryIds), function ($q) use ($accessibleCategoryIds) {
                $q->whereIn('category_id', $accessibleCategoryIds);
            })
            ->when(($context['is_partner'] ?? false) && empty($accessibleCategoryIds), function ($q) {
                $q->whereRaw('0 = 1');
            });

        if ($name = $request->input('name')) {
            $query->where('name', 'like', '%' . $name . '%');
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $perPage = (int) $request->input('per_page', 10);
        $products = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $categories = $this->availableCategoriesForPartner($context);

        if (! $this->hasProductCategoryColumn()) {
            $categories = collect();
        }

        return view('pages.products.index', [
            'products' => $products,
            'categories' => $categories,
            'canManageProducts' => $context['can_manage_products'],
            'activeOutlet' => $context['outlet']
        ]);
    }

    public function create()
    {
        $context = $this->resolveOutletContext();

        if (! $context['can_manage_products']) {
            return view('pages.products.request', [
                'categories' => $this->availableCategoriesForPartner($context),
                'activeOutlet' => $context['outlet'],
            ]);
        }

        $categories = Category::getFlattenedList(null, '', null);
        $materials = RawMaterial::query()
            ->accessibleBy(auth()->user())
            ->where('unit_cost', '>', 0)
            ->orderBy('name')
            ->get();

        $variantTemplates = $this->availableOptionGroups('variant', $context);
        $addonTemplates = $this->availableOptionGroups('addon', $context);
        $preferenceTemplates = $this->availableOptionGroups('preference', $context);
        $importableProducts = Product::whereIn('user_id', $context['owner_user_ids'])
            ->whereHas('optionGroups.optionGroup')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.products.create', [
            'categories' => $categories,
            'materials' => $materials,
            'product' => null,
            'recipe' => null,
            'variantTemplates' => $variantTemplates,
            'addonTemplates' => $addonTemplates,
            'preferenceTemplates' => $preferenceTemplates,
            'importableProducts' => $importableProducts,
        ]);
    }

    public function store(Request $request, RecipeService $recipes, ProductOptionService $options)
    {
        $context = $this->resolveOutletContext();

        if (! $context['can_manage_products']) {
            $this->handleProductRequest($request, $context);
            return redirect()->route('product.index')->with('success', 'Permintaan penambahan produk telah dikirim ke owner outlet.');
        }

        $userId = auth()->id();
        $this->sanitizeRecipeInput($request);
        $this->normalizePreferenceInput($request);

        $baseRules = [
            'name' => [
                'required', 
                'string', 
                'min:3', 
                'max:255', 
                Rule::unique('products', 'name')
                    ->where(function ($q) use ($userId, $context) {
                        return $q->where('user_id', $userId)
                            ->where('outlet_id', $context['outlet']->id)
                            ->whereNull('deleted_at');
                    })
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')
                ->where(fn ($q) => $q->where('user_id', $userId)->whereNull('deleted_at'))],
            'image' => ['nullable', 'image', 'max:2048'],
            'yield_qty' => ['nullable', 'numeric', 'min:0.0001'],
            'unit' => ['nullable', 'string', 'max:20'],
            'recipe' => ['nullable', 'array'],
            'recipe.*.raw_material_id' => ['required', 'integer', Rule::exists('raw_materials', 'id')],
            'recipe.*.qty_per_yield' => ['required', 'numeric', 'min:0.0001'],
            'recipe.*.waste_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        $payload = $request->all();

        $validator = Validator::make(
            $payload,
            array_merge($baseRules, $options->optionRules()),
            [],
            [
                'recipe' => 'resep',
                'recipe.*.raw_material_id' => 'bahan',
                'recipe.*.qty_per_yield' => 'takaran',
                'recipe.*.waste_pct' => 'waste %',
            ]
        );

        $options->attachIntegrityChecks($validator, $payload, null);

        $validated = $validator->validate();

        $recipeItems = collect($validated['recipe'] ?? []);

        $product = DB::transaction(function () use ($request, $validated, $userId, $recipeItems, $recipes, $options) {
            $product = new Product();
            $product->user_id = $userId;
            $product->name = $validated['name'];
            $product->price = (float) $validated['price'];
            $product->category_id = (int) $validated['category_id'];
            $product->stock = 0; // akan dihitung ulang setelah resep tersimpan
            $product->sync_status = 'pending';
            $product->last_synced = null;
            $product->client_version = 'web';
            $product->version_id = 1;

            if ($request->hasFile('image')) {
                $filename = time() . '.' . $request->file('image')->extension();
                $request->file('image')->move(public_path('products'), $filename);
                $product->image = $filename;
            } else {
                $product->image = 'roar-logo.png';
            }

            $product->save();

            $unit = array_key_exists('unit', $validated) ? trim((string) $validated['unit']) : null;
            if ($unit === '') {
                $unit = null;
            }

            $yieldQty = $validated['yield_qty'] ?? null;

            $this->syncRecipe($product, $yieldQty, $unit, $recipeItems);
            $this->refreshProductStats($product, $recipes);
            $options->syncVariantGroups($product, $request->input('variant_groups', []));
            $options->syncAddonGroups($product, $request->input('addon_groups', []));
            $options->syncPreferenceGroups($product, $request->input('preference_groups', []));

            return $product;
        });

        return redirect()->route('product.index')->with('success', 'Produk berhasil dibuat.');
    }

    public function edit($id)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageProducts($context);

        $userId = auth()->id();
        $product = Product::where('user_id', $userId)
            ->with(['optionGroups.optionGroup', 'optionGroups.optionItems.optionItem'])
            ->findOrFail($id);
        $categories = Category::getFlattenedList(null, '', null);
        $recipe = ProductRecipe::with('items')->where('product_id', $product->id)->first();
        $materials = RawMaterial::query()
            ->accessibleBy(auth()->user())
            ->where(function ($query) use ($recipe) {
                $query->where('unit_cost', '>', 0);
                if ($recipe && $recipe->items) {
                    $ids = $recipe->items->pluck('raw_material_id')->filter()->all();
                    if (! empty($ids)) {
                        $query->orWhereIn('id', $ids);
                    }
                }
            })
            ->orderBy('name')
            ->get();

        $variantTemplates = $this->availableOptionGroups('variant', $context);
        $addonTemplates = $this->availableOptionGroups('addon', $context);
        $preferenceTemplates = $this->availableOptionGroups('preference', $context);
        $importableProducts = Product::whereIn('user_id', $context['owner_user_ids'])
            ->where('id', '!=', $product->id)
            ->whereHas('optionGroups.optionGroup')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'materials' => $materials,
            'recipe' => $recipe,
            'variantTemplates' => $variantTemplates,
            'addonTemplates' => $addonTemplates,
            'preferenceTemplates' => $preferenceTemplates,
            'importableProducts' => $importableProducts,
        ]);
    }

    public function update(Request $request, $id, RecipeService $recipes, ProductOptionService $options)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageProducts($context);

        $userId = auth()->id();
        $product = Product::where('user_id', $userId)
            ->with(['optionGroups.optionGroup', 'optionGroups.optionItems.optionItem'])
            ->findOrFail($id);

        $this->sanitizeRecipeInput($request);
        $this->normalizePreferenceInput($request);

        $baseRules = [
            'name' => [
                'required', 
                'string', 
                'min:3', 
                'max:255', 
                Rule::unique('products', 'name')
                    ->ignore($product->id)
                    ->where(function ($q) use ($userId, $product) {
                        return $q->where('user_id', $userId)
                            ->where('outlet_id', $product->outlet_id)
                            ->whereNull('deleted_at');
                    })
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')
                ->where(fn ($q) => $q->where('user_id', $userId)->whereNull('deleted_at'))],
            'image' => ['nullable', 'image', 'max:2048'],
            'yield_qty' => ['nullable', 'numeric', 'min:0.0001'],
            'unit' => ['nullable', 'string', 'max:20'],
            'recipe' => ['nullable', 'array'],
            'recipe.*.raw_material_id' => ['required', 'integer', Rule::exists('raw_materials', 'id')],
            'recipe.*.qty_per_yield' => ['required', 'numeric', 'min:0.0001'],
            'recipe.*.waste_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        $payload = $request->all();

        $validator = Validator::make(
            $payload,
            array_merge($baseRules, $options->optionRules($product)),
            [],
            [
                'recipe' => 'resep',
                'recipe.*.raw_material_id' => 'bahan',
                'recipe.*.qty_per_yield' => 'takaran',
                'recipe.*.waste_pct' => 'waste %',
            ]
        );

        $options->attachIntegrityChecks($validator, $payload, $product);

        $validated = $validator->validate();

        $recipeItems = collect($validated['recipe'] ?? []);

        DB::transaction(function () use ($request, $product, $validated, $recipeItems, $recipes, $options) {
            $product->name = $validated['name'];
            $product->price = (float) $validated['price'];
            $product->category_id = (int) $validated['category_id'];

            if ($request->hasFile('image')) {
                if ($product->image && $product->image !== 'roar-logo.png') {
                    $oldPath = public_path('products/' . $product->image);
                    if (file_exists($oldPath)) {
@unlink($oldPath);
                    }
                }
                $filename = time() . '.' . $request->file('image')->extension();
                $request->file('image')->move(public_path('products'), $filename);
                $product->image = $filename;
            }

            $product->sync_status = 'pending';
            $product->last_synced = null;
            $product->client_version = 'web';
            $product->version_id = (int) $product->version_id + 1;
            $product->save();

            $unit = array_key_exists('unit', $validated) ? trim((string) $validated['unit']) : null;
            if ($unit === '') {
                $unit = null;
            }

            $yieldQty = $validated['yield_qty'] ?? null;

            $this->syncRecipe($product, $yieldQty, $unit, $recipeItems);
            $this->refreshProductStats($product, $recipes);
            $options->syncVariantGroups($product, $request->input('variant_groups', []));
            $options->syncAddonGroups($product, $request->input('addon_groups', []));
            $options->syncPreferenceGroups($product, $request->input('preference_groups', []));
        });

        return redirect()->route('product.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        $context = $this->resolveOutletContext();

        $userId = auth()->id();
        if (! $context['can_manage_products']) {
            $product = Product::whereIn('user_id', $context['owner_user_ids'])->findOrFail($id);
            $this->handleProductRequest($request, $context, 'delete', $product);
            return redirect()->route('product.index')->with('success', 'Permintaan penghapusan produk telah dikirim ke owner outlet.');
        }

        $product = Product::where('user_id', $userId)->findOrFail($id);
        if ($product->image && $product->image !== 'roar-logo.png') {
            $path = public_path('products/' . $product->image);
            if (file_exists($path)) {
@unlink($path);
            }
        }
        $product->delete();

        return redirect()->route('product.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function requestCreate(Request $request)
    {
        $context = $this->resolveOutletContext();

        if ($context['can_manage_products']) {
            return redirect()->route('product.create');
        }

        $this->handleProductRequest($request, $context, 'create');

        return redirect()->route('product.index')->with('success', 'Permintaan penambahan produk telah dikirim ke owner outlet.');
    }

    public function optionPresets(Request $request, Product $product)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageProducts($context);

        $importSource = Product::with(['optionGroups.optionGroup', 'optionGroups.optionItems.optionItem'])
            ->whereIn('user_id', $context['owner_user_ids'])
            ->findOrFail($product->id);

        $variantGroups = $importSource->optionGroups
            ->filter(fn (ProductOptionGroup $group) => $group->optionGroup?->type === 'variant')
            ->values()
            ->map(fn (ProductOptionGroup $group) => $this->transformOptionGroupForImport($group))
            ->all();

        $addonGroups = $importSource->optionGroups
            ->filter(fn (ProductOptionGroup $group) => $group->optionGroup?->type === 'addon')
            ->values()
            ->map(fn (ProductOptionGroup $group) => $this->transformOptionGroupForImport($group))
            ->all();

        $preferenceGroups = $importSource->optionGroups
            ->filter(fn (ProductOptionGroup $group) => $group->optionGroup?->type === 'preference')
            ->values()
            ->map(fn (ProductOptionGroup $group) => $this->transformOptionGroupForImport($group))
            ->all();

        return response()->json([
            'product' => [
                'id' => $importSource->id,
                'name' => $importSource->name,
            ],
            'variant_groups' => $variantGroups,
            'addon_groups' => $addonGroups,
            'preference_groups' => $preferenceGroups,
        ]);
    }

    public function requestEdit($id)
    {
        $context = $this->resolveOutletContext();

        if ($context['can_manage_products']) {
            return redirect()->route('product.edit', $id);
        }

        $product = Product::whereIn('user_id', $context['owner_user_ids'])->with('category')->findOrFail($id);
        $categories = $this->availableCategoriesForPartner($context);

        return view('pages.products.request_edit', [
            'product' => $product,
            'categories' => $categories,
            'activeOutlet' => $context['outlet'],
        ]);
    }

    public function requestUpdate(Request $request, $id)
    {
        $context = $this->resolveOutletContext();

        if ($context['can_manage_products']) {
            return redirect()->route('product.edit', $id);
        }

        $product = Product::whereIn('user_id', $context['owner_user_ids'])->with('category')->findOrFail($id);

        $this->handleProductRequest($request, $context, 'update', $product);

        return redirect()->route('product.index')->with('success', 'Permintaan perubahan produk telah dikirim ke owner outlet.');
    }

    public function requestDelete(Request $request, $id)
    {
        $context = $this->resolveOutletContext();

        if ($context['can_manage_products']) {
            return redirect()->route('product.edit', $id);
        }

        $product = Product::whereIn('user_id', $context['owner_user_ids'])->with('category')->findOrFail($id);

        $this->handleProductRequest($request, $context, 'delete', $product);

        return redirect()->route('product.index')->with('success', 'Permintaan penghapusan produk telah dikirim ke owner outlet.');
    }

    private function handleProductRequest(Request $request, array $context, string $action = 'create', ?Product $product = null): void
    {
        $categories = $this->availableCategoriesForPartner($context);
        $categoryRule = $categories->isEmpty() ? 'nullable' : Rule::in($categories->pluck('id')->map(fn($id) => (string) $id));

        $payload = ['action' => $action];

        if ($action === 'create') {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'category_id' => [$categoryRule],
                'price' => ['nullable', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string', 'max:500'],
            ]);

            $categoryName = null;
            if (! empty($validated['category_id'])) {
                $categoryName = optional($categories->firstWhere('id', (int) $validated['category_id']))->name;
            }

            $payload['name'] = $validated['name'];
            $payload['price'] = $validated['price'] ?? null;
            $payload['category_name'] = $categoryName;
            $payload['proposed'] = [
                'name' => $validated['name'],
                'price' => $validated['price'] ?? null,
                'category_name' => $categoryName,
            ];
            $payload['notes'] = $validated['notes'] ?? null;
        } elseif ($action === 'update' && $product) {
            $validated = $request->validate([
                'name' => ['nullable', 'string', 'max:255'],
                'category_id' => [$categoryRule],
                'price' => ['nullable', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string', 'max:500'],
            ]);

            $categoryName = null;
            if (! empty($validated['category_id'])) {
                $categoryName = optional($categories->firstWhere('id', (int) $validated['category_id']))->name;
            }

            $payload['product'] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'category_name' => optional($product->category)->name,
            ];
            $payload['proposed'] = [
                'name' => $validated['name'] ?? null,
                'price' => $validated['price'] ?? null,
                'category_name' => $categoryName,
            ];
            $payload['notes'] = $validated['notes'] ?? null;
        } elseif ($action === 'delete' && $product) {
            $validated = $request->validate([
                'notes' => ['nullable', 'string', 'max:500'],
            ]);

            $payload['product'] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'category_name' => optional($product->category)->name,
            ];
            $payload['notes'] = $validated['notes'] ?? null;
        } else {
            return;
        }

        $outlet = $context['outlet'];
        $owners = $outlet?->owners ?? collect();

        if ($owners->isEmpty()) {
            return;
        }

        foreach ($owners as $owner) {
            if (! $owner->email) {
                continue;
            }

            Mail::to($owner->email)->send(new PartnerProductRequestMail(
                auth()->user(),
                $outlet,
                $payload
            ));
        }
    }

    private function resolveOutletContext(): array
    {
        $user = auth()->user();
        $activeOutlet = OutletContext::currentOutlet();
        $currentRole = OutletContext::currentRole();

        $ownerUserIds = [];
        if ($activeOutlet) {
            $ownerUserIds = $activeOutlet->owners()->pluck('users.id')->unique()->values()->all();
        }
        if (empty($ownerUserIds)) {
            $ownerUserIds = [$user?->id];
        }

        $accessibleCategoryIds = [];
        if ($currentRole && $currentRole->role === 'partner' && $activeOutlet) {
            /** @var PartnerCategoryAccessService $access */
            $access = app(PartnerCategoryAccessService::class);
            $cats = $access->accessibleCategoryIdsFor($user, $activeOutlet);
            $accessibleCategoryIds = $cats === ['*'] ? ['*'] : (array) $cats;
        }

        $isPartner = $currentRole && $currentRole->role === 'partner';
        $canManageProducts = $user?->roles === 'admin'
            || ($currentRole && $currentRole->role === 'owner');

        return [
            'outlet' => $activeOutlet,
            'owner_user_ids' => $ownerUserIds,
            'accessible_category_ids' => $accessibleCategoryIds,
            'is_partner' => $isPartner,
            'can_manage_products' => $canManageProducts,
        ];
    }

    private function shouldFilterCategories(array $accessibleCategoryIds): bool
    {
        return $this->hasProductCategoryColumn() && ! empty($accessibleCategoryIds) && $accessibleCategoryIds !== ['*'];
    }

    private function hasProductCategoryColumn(): bool
    {
        static $cached = null;
        if ($cached === null) {
            $cached = Schema::hasColumn('products', 'category_id');
        }

        return $cached;
    }

    private function ensureCanManageProducts(array $context): void
    {
        if (! $context['can_manage_products']) {
            abort(403, 'Hanya owner outlet yang dapat mengelola produk.');
        }
    }

    private function availableCategoriesForPartner(array $context)
    {
        if (! $this->hasProductCategoryColumn()) {
            return collect();
        }

        $ownerUserIds = $context['owner_user_ids'];
        $accessibleCategoryIds = $context['accessible_category_ids'];
        $isPartner = $context['is_partner'] ?? false;

        // Get all categories first
        $query = Category::whereIn('user_id', $ownerUserIds);

        if ($this->shouldFilterCategories($accessibleCategoryIds)) {
            $query->whereIn('id', $accessibleCategoryIds);
        } elseif ($isPartner && empty($accessibleCategoryIds)) {
            return collect();
        }

        $allCategories = $query->orderBy('name')->get();
        
        // Filter and flatten for dropdown display
        if ($this->shouldFilterCategories($accessibleCategoryIds)) {
            $filteredCategories = $allCategories->filter(function ($category) use ($accessibleCategoryIds) {
                return in_array($category->id, $accessibleCategoryIds);
            });
            
            // Create flattened list with proper hierarchy
            $flattened = collect();
            $rootCategories = $filteredCategories->filter(function ($cat) {
                return is_null($cat->parent_id) || !in_array($cat->parent_id, $accessibleCategoryIds);
            })->sortBy('name');
            
            foreach ($rootCategories as $root) {
                $flattened->push((object) [
                    'id' => $root->id,
                    'name' => $root->name,
                    'level' => 0,
                ]);
                
                $this->addChildrenToFlattenedList($flattened, $root, $filteredCategories, 1);
            }
            
            return $flattened;
        } else {
            // Return all categories in flattened format
            return Category::getFlattenedList();
        }
    }
    
    /**
     * Helper method to add children to flattened list recursively
     */
    private function addChildrenToFlattenedList(&$flattened, $parent, $allCategories, $level)
    {
        $children = $allCategories->filter(function ($cat) use ($parent) {
            return $cat->parent_id == $parent->id;
        })->sortBy('name');
        
        foreach ($children as $child) {
            $flattened->push((object) [
                'id' => $child->id,
                'name' => str_repeat('─ ', $level) . $child->name,
                'level' => $level,
            ]);
            
            $this->addChildrenToFlattenedList($flattened, $child, $allCategories, $level + 1);
        }
    }

    private function sanitizeRecipeInput(Request $request): void
    {
        $raw = $request->input('recipe', []);
        $cleaned = [];
        foreach ($raw as $row) {
            $materialId = (int) ($row['raw_material_id'] ?? 0);
            $qty = isset($row['qty_per_yield']) ? (float) $row['qty_per_yield'] : null;
            if ($materialId > 0 && $qty !== null && $qty > 0) {
                $cleaned[] = [
                    'raw_material_id' => $materialId,
                    'qty_per_yield' => $qty,
                    'waste_pct' => isset($row['waste_pct']) ? (float) $row['waste_pct'] : 0.0,
                ];
            }
        }
        $request->merge(['recipe' => $cleaned]);
    }

    private function normalizePreferenceInput(Request $request): void
    {
        if (! $request->has('preference_groups') && $request->has('modifier_groups')) {
            $request->merge([
                'preference_groups' => $request->input('modifier_groups'),
            ]);
        }
    }

    private function syncRecipe(Product $product, ?float $yieldQty, ?string $unit, Collection $items): void
    {
        $items = $items->filter(function (array $item) {
            return !empty($item['raw_material_id']) && isset($item['qty_per_yield']);
        })->values();

        if ($items->isEmpty()) {
            $existing = ProductRecipe::where('product_id', $product->id)->first();
            if ($existing) {
                $existing->items()->delete();
                $existing->delete();
            }
            return;
        }

        $recipe = ProductRecipe::updateOrCreate(
            ['product_id' => $product->id],
            [
                'yield_qty' => $yieldQty ?? 1,
                'unit' => $unit ?: null,
            ]
        );

        $recipe->items()->delete();
        foreach ($items as $item) {
            ProductRecipeItem::create([
                'product_recipe_id' => $recipe->id,
                'raw_material_id' => $item['raw_material_id'],
                'qty_per_yield' => $item['qty_per_yield'],
                'waste_pct' => $item['waste_pct'] ?? 0,
            ]);
        }
    }

    private function refreshProductStats(Product $product, RecipeService $recipes): void
    {
        $product->refresh();
        $product->cost_price = $recipes->calculateCogs($product);
        $estimate = $recipes->estimateBuildableUnits($product);
        $product->stock = $estimate !== null ? max(0, (int) $estimate) : 0;
        $product->save();
    }

    protected function transformOptionGroupForImport(ProductOptionGroup $group): array
    {
        $type = $group->optionGroup?->type ?? 'variant';
        $itemsKey = $this->itemsKeyForImport($type);

        $optionItems = $group->optionItems
            ->sortBy('sort_order')
            ->values()
            ->map(function (ProductOptionItem $item) use ($type) {
                $option = $item->optionItem;

                $payload = [
                    'id' => $item->id,
                    'option_item_id' => $item->option_item_id,
                    'name' => $option?->name,
                    'price_adjustment' => $item->resolvedPriceAdjustment(),
                    'is_default' => $item->resolvedIsDefault(),
                    'is_active' => $item->resolvedIsActive(),
                    'sort_order' => $item->sort_order,
                ];

                if ($type === 'variant') {
                    $payload['stock'] = $item->resolvedStock();
                    $payload['sku'] = $item->resolvedSku();
                } else {
                    $payload['max_quantity'] = $item->resolvedMaxQuantity();
                }

                return $payload;
            })
            ->values()
            ->all();

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
            $itemsKey => $optionItems,
        ];
    }

    protected function itemsKeyForImport(string $type): string
    {
        switch ($type) {
            case 'addon':
                return 'addons';
            case 'preference':
                return 'preferences';
            default:
                return 'variants';
        }
    }

    private function availableOptionGroups(string $type, array $context)
    {
        $ownerUserIds = $context['owner_user_ids'] ?? [];

        return OptionGroup::with('items')
            ->where('type', $type)
            ->where(function ($query) use ($ownerUserIds) {
                $query->whereNull('user_id');
                if (! empty($ownerUserIds)) {
                    $query->orWhereIn('user_id', $ownerUserIds);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Quick update product price via AJAX
     */
    public function quickUpdatePrice(Request $request, $id)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageProducts($context);

        $userId = auth()->id();
        $product = Product::where('user_id', $userId)->findOrFail($id);

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ]);

        DB::transaction(function () use ($product, $validated) {
            $product->price = (float) $validated['price'];
            $product->sync_status = 'pending';
            $product->last_synced = null;
            $product->client_version = 'web';
            $product->version_id = (int) $product->version_id + 1;
            $product->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Harga produk berhasil diperbarui.',
            'data' => [
                'price' => $product->price,
                'formatted_price' => number_format($product->price, 0, ',', '.'),
            ]
        ]);
    }

    /**
     * Quick update product category via AJAX
     */
    public function quickUpdateCategory(Request $request, $id)
    {
        $context = $this->resolveOutletContext();
        $this->ensureCanManageProducts($context);

        $userId = auth()->id();
        $product = Product::where('user_id', $userId)->findOrFail($id);

        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')
                ->where(fn ($q) => $q->where('user_id', $userId)->whereNull('deleted_at'))],
        ]);

        $category = Category::where('user_id', $userId)->findOrFail($validated['category_id']);

        DB::transaction(function () use ($product, $validated, $category) {
            $product->category_id = (int) $validated['category_id'];
            $product->sync_status = 'pending';
            $product->last_synced = null;
            $product->client_version = 'web';
            $product->version_id = (int) $product->version_id + 1;
            $product->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Kategori produk berhasil diperbarui.',
            'data' => [
                'category_id' => $product->category_id,
                'category_name' => $category->name,
                'category_full_path' => $category->parent_id ? $category->full_path : $category->name,
            ]
        ]);
    }
}
