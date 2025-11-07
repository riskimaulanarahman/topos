<?php

namespace App\Services;

use App\Models\Category;
use App\Models\DuplicationJob;
use App\Models\DuplicationJobItem;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\User;
use App\Notifications\DuplicationJobFinished;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CatalogDuplicationService
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {
    }

    /**
     * Create duplication job placeholder and perform basic validation.
     */
    public function createJob(
        User $user,
        Outlet $source,
        Outlet $target,
        array $requestedResources,
        array $options = []
    ): DuplicationJob {
        if ($source->id === $target->id) {
            throw ValidationException::withMessages([
                'target_outlet_id' => __('Outlet tujuan harus berbeda dengan sumber.'),
            ]);
        }

        $resources = $this->normalizeRequestedResources($requestedResources);

        return DuplicationJob::create([
            'source_outlet_id' => $source->id,
            'target_outlet_id' => $target->id,
            'requested_by' => $user->id,
            'status' => DuplicationJob::STATUS_PENDING,
            'requested_resources' => $resources,
            'options' => $options,
        ]);
    }

    /**
     * Execute duplication job.
     */
    public function runJob(DuplicationJob $job): DuplicationJob
    {
        $job->update([
            'status' => DuplicationJob::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        $counts = [
            'categories' => 0,
            'raw_materials' => 0,
            'products' => 0,
            'failed' => 0,
        ];

        $errors = [];

        try {
            DB::transaction(function () use ($job, &$counts, &$errors) {
                $mappings = [
                    'categories' => [],
                    'raw_materials' => [],
                    'products' => [],
                ];

                if ($this->shouldDuplicate($job, 'categories')) {
                    $mappings['categories'] = $this->duplicateCategories($job, $counts, $errors);
                }

                if ($this->shouldDuplicate($job, 'raw_materials')) {
                    $mappings['raw_materials'] = $this->duplicateRawMaterials($job, $mappings['categories'], $counts, $errors);
                }

                if ($this->shouldDuplicate($job, 'products')) {
                    $this->duplicateProducts($job, $mappings, $counts, $errors);
                }
            });
        } catch (\Throwable $e) {
            $job->update([
                'status' => DuplicationJob::STATUS_FAILED,
                'counts' => $counts,
                'error_log' => array_merge($errors, [$e->getMessage()]),
                'finished_at' => now(),
            ]);
            report($e);

            $job = $job->refresh();
            $job->requester?->notify(new DuplicationJobFinished($job));

            return $job;
        }

        $job->update([
            'status' => $counts['failed'] > 0 ? DuplicationJob::STATUS_PARTIAL : DuplicationJob::STATUS_COMPLETED,
            'counts' => $counts,
            'error_log' => $errors,
            'finished_at' => now(),
        ]);

        $job = $job->refresh();
        $job->requester?->notify(new DuplicationJobFinished($job));

        return $job;
    }

    protected function duplicateCategories(
        DuplicationJob $job,
        array &$counts,
        array &$errors
    ): array {
        $requestedIds = Arr::get($job->requested_resources, 'category_ids', []);

        $query = Category::query()->forOutlet($job->source_outlet_id);
        if (! empty($requestedIds)) {
            $query->whereIn('id', $requestedIds);
        }

        $categories = $query->get();
        $mapping = [];

        foreach ($categories as $category) {
            $item = $this->createJobItem($job, 'category', $category->id);
            try {
                $duplicate = $category->replicate([
                    'outlet_id',
                    'user_id',
                    'created_at',
                    'updated_at',
                ]);
                $duplicate->outlet_id = $job->target_outlet_id;
                $duplicate->user_id = $job->requested_by;
                $duplicate->save();

                $mapping[$category->id] = $duplicate->id;
                $item->update([
                    'status' => DuplicationJobItem::STATUS_COMPLETED,
                    'target_id' => $duplicate->id,
                ]);
                $counts['categories']++;
            } catch (\Throwable $e) {
                $item->update([
                    'status' => DuplicationJobItem::STATUS_FAILED,
                    'notes' => $e->getMessage(),
                ]);
                $errors[] = sprintf('Kategori %s gagal: %s', $category->name, $e->getMessage());
                $counts['failed']++;
            }
        }

        return $mapping;
    }

    protected function duplicateRawMaterials(
        DuplicationJob $job,
        array $categoryMapping,
        array &$counts,
        array &$errors
    ): array {
        $requestedIds = Arr::get($job->requested_resources, 'raw_material_ids', []);
        $copyStock = (bool) Arr::get($job->options, 'copy_stock', false);

        $query = RawMaterial::query()->forOutlet($job->source_outlet_id)->with('categories:id');
        if (! empty($requestedIds)) {
            $query->whereIn('id', $requestedIds);
        }

        $materials = $query->get();
        $mapping = [];

        foreach ($materials as $material) {
            $item = $this->createJobItem($job, 'raw_material', $material->id);
            try {
                $duplicate = $material->replicate([
                    'outlet_id',
                    'created_by',
                    'updated_by',
                    'stock_qty',
                    'unit_cost',
                    'created_at',
                    'updated_at',
                ]);
                $duplicate->outlet_id = $job->target_outlet_id;
                $duplicate->created_by = $job->requested_by;
                $duplicate->updated_by = $job->requested_by;
                $duplicate->sku = RawMaterial::generateSku($material->name);
                $duplicate->stock_qty = $copyStock ? $material->stock_qty : 0;
                $duplicate->unit_cost = $material->unit_cost;
                $duplicate->save();

                if ($material->categories->isNotEmpty()) {
                    $targetCategoryIds = $material->categories
                        ->pluck('id')
                        ->map(fn ($id) => $categoryMapping[$id] ?? null)
                        ->filter()
                        ->all();
                    if (! empty($targetCategoryIds)) {
                        $duplicate->categories()->sync($targetCategoryIds);
                    }
                }

                if ($copyStock && $material->stock_qty > 0) {
                    $this->inventoryService->adjustStock(
                        $duplicate,
                        (float) $material->stock_qty,
                        'adjustment',
                        (float) $material->unit_cost,
                        'opening_balance',
                        $duplicate->id,
                        'Duplicate from outlet '.$job->source_outlet_id
                    );
                }

                $mapping[$material->id] = $duplicate->id;
                $item->update([
                    'status' => DuplicationJobItem::STATUS_COMPLETED,
                    'target_id' => $duplicate->id,
                ]);
                $counts['raw_materials']++;
            } catch (\Throwable $e) {
                $item->update([
                    'status' => DuplicationJobItem::STATUS_FAILED,
                    'notes' => $e->getMessage(),
                ]);
                $errors[] = sprintf('Bahan baku %s gagal: %s', $material->name, $e->getMessage());
                $counts['failed']++;
            }
        }

        return $mapping;
    }

    protected function duplicateProducts(
        DuplicationJob $job,
        array $mappings,
        array &$counts,
        array &$errors
    ): void {
        $requestedIds = Arr::get($job->requested_resources, 'product_ids', []);

        $query = Product::query()
            ->forOutlet($job->source_outlet_id)
            ->with(['category:id', 'recipe.items']);

        if (! empty($requestedIds)) {
            $query->whereIn('id', $requestedIds);
        }

        $products = $query->get();

        foreach ($products as $product) {
            $item = $this->createJobItem($job, 'product', $product->id);
            try {
                $duplicate = $product->replicate([
                    'outlet_id',
                    'user_id',
                    'client_version',
                    'version_id',
                    'sync_status',
                    'created_at',
                    'updated_at',
                ]);
                $duplicate->outlet_id = $job->target_outlet_id;
                $duplicate->user_id = $job->requested_by;

                if ($product->category_id && isset($mappings['categories'][$product->category_id])) {
                    $duplicate->category_id = $mappings['categories'][$product->category_id];
                } else {
                    $duplicate->category_id = null;
                }

                $duplicate->save();

                if ($product->relationLoaded('recipe') && $product->recipe) {
                    $recipe = $product->recipe->replicate(['product_id']);
                    $recipe->product_id = $duplicate->id;
                    $recipe->save();

                    $items = $product->recipe->items;
                    foreach ($items as $recipeItem) {
                        $targetRawMaterialId = $mappings['raw_materials'][$recipeItem->raw_material_id] ?? null;
                        if (! $targetRawMaterialId) {
                            continue;
                        }
                        $newItem = $recipeItem->replicate(['product_recipe_id', 'raw_material_id']);
                        $newItem->product_recipe_id = $recipe->id;
                        $newItem->raw_material_id = $targetRawMaterialId;
                        $newItem->save();
                    }
                }

                $item->update([
                    'status' => DuplicationJobItem::STATUS_COMPLETED,
                    'target_id' => $duplicate->id,
                ]);
                $counts['products']++;
            } catch (\Throwable $e) {
                $item->update([
                    'status' => DuplicationJobItem::STATUS_FAILED,
                    'notes' => $e->getMessage(),
                ]);
                $errors[] = sprintf('Produk %s gagal: %s', $product->name, $e->getMessage());
                $counts['failed']++;
            }
        }
    }

    protected function normalizeRequestedResources(array $requested): array
    {
        return [
            'categories' => (bool) Arr::get($requested, 'categories', false),
            'raw_materials' => (bool) Arr::get($requested, 'raw_materials', false),
            'products' => (bool) Arr::get($requested, 'products', false),
            'category_ids' => array_values(array_unique(Arr::get($requested, 'category_ids', []))),
            'raw_material_ids' => array_values(array_unique(Arr::get($requested, 'raw_material_ids', []))),
            'product_ids' => array_values(array_unique(Arr::get($requested, 'product_ids', []))),
        ];
    }

    protected function shouldDuplicate(DuplicationJob $job, string $resource): bool
    {
        $resources = Arr::get($job->requested_resources, $resource.'_ids', []);
        $flag = Arr::get($job->requested_resources, $resource, null);

        if (is_array($resources) && ! empty($resources)) {
            return true;
        }

        return (bool) $flag;
    }

    protected function createJobItem(DuplicationJob $job, string $type, int $sourceId): DuplicationJobItem
    {
        return DuplicationJobItem::create([
            'duplication_job_id' => $job->id,
            'entity_type' => $type,
            'source_id' => $sourceId,
            'status' => DuplicationJobItem::STATUS_PENDING,
        ]);
    }
}
