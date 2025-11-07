<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesOutlet;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Scopes\OutletScope;
use App\Services\ProductOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SyncController extends Controller
{
    use ResolvesOutlet;

    public function __construct(private ProductOptionService $optionService)
    {
    }
    /**
     * Batch sync categories from client to server
     */
    public function batchSyncCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categories' => 'required|array',
            'categories.*.id' => 'nullable|integer',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.operation' => 'required|in:create,update,delete',
            'categories.*.client_version' => 'nullable|string',
            'categories.*.version_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = [];
        $userId = auth()->id();

        DB::beginTransaction();
        try {
            foreach ($request->categories as $categoryData) {
                $result = $this->processCategorySync($categoryData, $userId);
                $results[] = $result;
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Batch sync completed',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Batch category sync failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Batch sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch sync products from client to server
     */
    public function batchSyncProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => ['required', 'array', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = [];
        $user = $request->user();
        $userId = $user?->id;

        DB::beginTransaction();

        try {
            foreach ($request->input('products') as $index => $productData) {
                $operation = $productData['operation'] ?? null;
                if (! in_array($operation, ['create', 'update', 'delete'], true)) {
                    throw ValidationException::withMessages([
                        "products.$index.operation" => __('Operasi produk tidak valid.'),
                    ]);
                }

                $outletId = (int) ($productData['outlet_id'] ?? $this->resolveOutletId($request, true));
                $this->assertUserHasOutletAccess($user, $outletId);
                OutletScope::setActiveOutletId($outletId);

                $existingProduct = null;
                if (in_array($operation, ['update', 'delete'], true)) {
                    $productId = $productData['id'] ?? null;
                    if (! $productId) {
                        throw ValidationException::withMessages([
                            "products.$index.id" => __('ID produk diperlukan untuk operasi ini.'),
                        ]);
                    }

                    $existingProduct = Product::where('user_id', $userId)->find($productId);
                    if (! $existingProduct) {
                        throw ValidationException::withMessages([
                            "products.$index.id" => __('Produk tidak ditemukan.'),
                        ]);
                    }
                }

                $productValidator = $this->makeSyncProductValidator(
                    $productData,
                    $userId,
                    $outletId,
                    $existingProduct,
                    $operation
                );

                if ($productValidator->fails()) {
                    $errors = $this->prefixValidationErrors($productValidator->errors()->toArray(), "products.$index");
                    throw ValidationException::withMessages($errors);
                }

                $result = $this->processProductSync($productData, $userId, $outletId, $existingProduct);
                $results[] = $result;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Batch sync completed',
                'results' => $results,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch product sync failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Batch sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status for pending items
     */
    public function getSyncStatus(Request $request)
    {
        $userId = auth()->id();

        $pendingCategories = Category::where('user_id', $userId)
            ->where('sync_status', 'pending')
            ->count();

        $pendingProducts = Product::where('user_id', $userId)
            ->where('sync_status', 'pending')
            ->count();

        $conflictCategories = Category::where('user_id', $userId)
            ->where('sync_status', 'conflict')
            ->get(['id', 'name', 'version_id', 'updated_at']);

        $conflictProducts = Product::where('user_id', $userId)
            ->where('sync_status', 'conflict')
            ->get(['id', 'name', 'version_id', 'updated_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'pending_count' => [
                    'categories' => $pendingCategories,
                    'products' => $pendingProducts,
                ],
                'conflicts' => [
                    'categories' => $conflictCategories,
                    'products' => $conflictProducts,
                ],
                'last_check' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Resolve conflicts by accepting client or server version
     */
    public function resolveConflicts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conflicts' => 'required|array',
            'conflicts.*.type' => 'required|in:category,product',
            'conflicts.*.id' => 'required|integer',
            'conflicts.*.resolution' => 'required|in:client,server',
            'conflicts.*.client_data' => 'required_if:conflicts.*.resolution,client',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = [];
        $userId = auth()->id();

        DB::beginTransaction();
        try {
            foreach ($request->conflicts as $conflictData) {
                $result = $this->resolveConflict($conflictData, $userId);
                $results[] = $result;
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Conflicts resolved',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Conflict resolution failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Conflict resolution failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process individual category sync operation
     */
    private function processCategorySync($data, $userId)
    {
        try {
            switch ($data['operation']) {
                case 'create':
                    $category = Category::create([
                        'user_id' => $userId,
                        'name' => $data['name'],
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'client_version' => $data['client_version'] ?? null,
                        'version_id' => 1,
                    ]);
                    
                    return [
                        'operation' => 'create',
                        'client_id' => $data['id'] ?? null,
                        'server_id' => $category->id,
                        'success' => true,
                    ];

                case 'update':
                    $category = Category::where('user_id', $userId)
                        ->findOrFail($data['id']);
                    
                    // Check for conflicts
                    if (isset($data['version_id']) && $category->version_id != $data['version_id']) {
                        $category->sync_status = 'conflict';
                        $category->save();
                        
                        return [
                            'operation' => 'update',
                            'id' => $data['id'],
                            'success' => false,
                            'conflict' => true,
                            'server_version' => $category->version_id,
                            'client_version' => $data['version_id'],
                        ];
                    }

                    $category->update([
                        'name' => $data['name'],
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'client_version' => $data['client_version'] ?? null,
                        'version_id' => $category->version_id + 1,
                    ]);

                    return [
                        'operation' => 'update',
                        'id' => $data['id'],
                        'success' => true,
                        'new_version' => $category->version_id,
                    ];

                case 'delete':
                    $category = Category::where('user_id', $userId)
                        ->findOrFail($data['id']);
                    
                    // Check if category has products
                    if ($category->products()->count() > 0) {
                        return [
                            'operation' => 'delete',
                            'id' => $data['id'],
                            'success' => false,
                            'message' => 'Category has associated products',
                        ];
                    }

                    $category->delete();
                    
                    return [
                        'operation' => 'delete',
                        'id' => $data['id'],
                        'success' => true,
                    ];

                default:
                    return [
                        'operation' => $data['operation'],
                        'id' => $data['id'] ?? null,
                        'success' => false,
                        'message' => 'Invalid operation',
                    ];
            }
        } catch (\Exception $e) {
            return [
                'operation' => $data['operation'],
                'id' => $data['id'] ?? null,
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function makeSyncProductValidator(array $data, int $userId, int $outletId, ?Product $existingProduct, string $operation): ValidatorContract
    {
        $baseRules = [
            'operation' => ['required', 'in:create,update,delete'],
            'outlet_id' => ['required', 'integer', Rule::exists('outlets', 'id')],
        ];

        if ($operation === 'delete') {
            $baseRules['id'] = ['required', 'integer'];
            return Validator::make($data, $baseRules);
        }

        $productId = $existingProduct?->id;

        $detailRules = [
            'id' => $existingProduct ? ['required', 'integer'] : ['nullable', 'integer'],
            'name' => [
                'required', 'string', 'min:3', 'max:255',
                Rule::unique('products', 'name')
                    ->where(fn ($q) => $q
                        ->where('user_id', $userId)
                        ->where('outlet_id', $outletId)
                        ->whereNull('deleted_at'))
                    ->ignore($productId),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q
                    ->where('user_id', $userId)
                    ->where('outlet_id', $outletId)
                    ->whereNull('deleted_at')),
            ],
            'image' => ['nullable', 'string'],
            'client_version' => ['nullable', 'string'],
            'version_id' => ['nullable', 'integer'],
        ];

        $rules = array_merge($baseRules, $detailRules, $this->optionService->optionRules($existingProduct));

        $validator = Validator::make($data, $rules);
        $this->optionService->attachIntegrityChecks($validator, $data, $existingProduct);

        return $validator;
    }

    private function prefixValidationErrors(array $errors, string $prefix): array
    {
        $prefixed = [];

        foreach ($errors as $key => $messages) {
            $fullKey = trim($prefix . '.' . ltrim($key, '.'), '.');
            foreach ((array) $messages as $message) {
                $prefixed[$fullKey][] = $message;
            }
        }

        return $prefixed;
    }

    /**
     * Process individual product sync operation
     */
    private function processProductSync(array $data, int $userId, int $outletId, ?Product $existingProduct = null)
    {
        try {
            OutletScope::setActiveOutletId($outletId);
            $this->normalizePreferencePayload($data);

            switch ($data['operation']) {
                case 'create':
                    $product = new Product([
                        'user_id' => $userId,
                        'outlet_id' => $outletId,
                        'name' => $data['name'],
                        'price' => (int) round($data['price']),
                        'stock' => (int) $data['stock'],
                        'category_id' => $data['category_id'],
                        'image' => $data['image'] ?? null,
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'client_version' => $data['client_version'] ?? null,
                        'version_id' => 1,
                    ]);

                    $product->save();

                    if (array_key_exists('variant_groups', $data)) {
                        $this->optionService->syncVariantGroups($product, $data['variant_groups'] ?? [], 'synced');
                    }

                    if (array_key_exists('addon_groups', $data)) {
                        $this->optionService->syncAddonGroups($product, $data['addon_groups'] ?? [], 'synced');
                    }
                    if (array_key_exists('preference_groups', $data)) {
                        $this->optionService->syncPreferenceGroups($product, $data['preference_groups'] ?? [], 'synced');
                    }

                    return [
                        'operation' => 'create',
                        'client_id' => $data['id'] ?? null,
                        'server_id' => $product->id,
                        'success' => true,
                    ];

                case 'update':
                    $product = $existingProduct ?? Product::where('user_id', $userId)->findOrFail($data['id']);

                    if (isset($data['version_id']) && $product->version_id != $data['version_id']) {
                        $product->sync_status = 'conflict';
                        $product->save();

                        return [
                            'operation' => 'update',
                            'id' => $product->id,
                            'success' => false,
                            'conflict' => true,
                            'server_version' => $product->version_id,
                            'client_version' => $data['version_id'],
                        ];
                    }

                    $product->fill([
                        'name' => $data['name'],
                        'price' => (int) round($data['price']),
                        'stock' => (int) $data['stock'],
                        'category_id' => $data['category_id'],
                        'image' => $data['image'] ?? $product->image,
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'client_version' => $data['client_version'] ?? null,
                        'version_id' => $product->version_id + 1,
                    ]);

                    $product->save();

                    if (array_key_exists('variant_groups', $data)) {
                        $this->optionService->syncVariantGroups($product, $data['variant_groups'] ?? [], 'synced');
                    }

                    if (array_key_exists('addon_groups', $data)) {
                        $this->optionService->syncAddonGroups($product, $data['addon_groups'] ?? [], 'synced');
                    }
                    if (array_key_exists('preference_groups', $data)) {
                        $this->optionService->syncPreferenceGroups($product, $data['preference_groups'] ?? [], 'synced');
                    }

                    return [
                        'operation' => 'update',
                        'id' => $product->id,
                        'success' => true,
                        'new_version' => $product->version_id,
                    ];

                case 'delete':
                    $product = $existingProduct ?? Product::where('user_id', $userId)->findOrFail($data['id']);

                    if ($product->orderItems()->exists()) {
                        return [
                            'operation' => 'delete',
                            'id' => $product->id,
                            'success' => false,
                            'message' => 'Product has associated orders',
                        ];
                    }

                    $product->delete();

                    return [
                        'operation' => 'delete',
                        'id' => $data['id'],
                        'success' => true,
                    ];

                default:
                    return [
                        'operation' => $data['operation'],
                        'id' => $data['id'] ?? null,
                        'success' => false,
                        'message' => 'Invalid operation',
                    ];
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return [
                'operation' => $data['operation'],
                'id' => $data['id'] ?? null,
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve individual conflict
     */
    private function resolveConflict($data, $userId)
    {
        try {
            if ($data['type'] === 'category') {
                $model = Category::where('user_id', $userId)->findOrFail($data['id']);
            } else {
                $model = Product::where('user_id', $userId)->findOrFail($data['id']);
            }

            if ($data['resolution'] === 'client') {
                // Accept client version
                $clientData = $data['client_data'];
                
                if ($data['type'] === 'category') {
                    $model->update([
                        'name' => $clientData['name'],
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'version_id' => $model->version_id + 1,
                    ]);
                } else {
                    $model->update([
                        'name' => $clientData['name'],
                        'price' => $clientData['price'],
                        'stock' => $clientData['stock'],
                        'category_id' => $clientData['category_id'],
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'version_id' => $model->version_id + 1,
                    ]);
                }
            } else {
                // Accept server version (just mark as synced)
                $model->update([
                    'sync_status' => 'synced',
                    'last_synced' => now(),
                ]);
            }

            return [
                'type' => $data['type'],
                'id' => $data['id'],
                'resolution' => $data['resolution'],
                'success' => true,
                'new_version' => $model->version_id,
            ];
        } catch (\Exception $e) {
            return [
                'type' => $data['type'],
                'id' => $data['id'],
                'resolution' => $data['resolution'],
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function normalizePreferencePayload(array &$data): void
    {
        if (! array_key_exists('preference_groups', $data) && array_key_exists('modifier_groups', $data)) {
            $data['preference_groups'] = $data['modifier_groups'];
        }
    }
}
