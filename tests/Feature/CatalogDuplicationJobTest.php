<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DuplicationJob;
use App\Models\Outlet;
use App\Models\OutletUserRole;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Models\RawMaterial;
use App\Models\User;
use App\Notifications\DuplicationJobFinished;
use App\Services\CatalogDuplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CatalogDuplicationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_duplication_job_duplicates_selected_resources(): void
    {
        Notification::fake();

        $user = User::factory()->create(['roles' => 'admin']);

        $sourceOutlet = Outlet::create([
            'name' => 'Outlet Sumber',
            'code' => 'SRC'.uniqid(),
            'created_by' => $user->id,
        ]);

        $targetOutlet = Outlet::create([
            'name' => 'Outlet Tujuan',
            'code' => 'TGT'.uniqid(),
            'created_by' => $user->id,
        ]);

        OutletUserRole::create([
            'outlet_id' => $sourceOutlet->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
            'can_manage_stock' => true,
        ]);

        OutletUserRole::create([
            'outlet_id' => $targetOutlet->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
            'can_manage_stock' => true,
        ]);

        $category = Category::create([
            'outlet_id' => $sourceOutlet->id,
            'name' => 'Kue',
        ]);

        $rawMaterial = RawMaterial::create([
            'outlet_id' => $sourceOutlet->id,
            'name' => 'Gula',
            'sku' => 'GULA-001',
            'unit' => 'kg',
            'unit_cost' => 15,
            'stock_qty' => 5,
            'min_stock' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $rawMaterial->categories()->sync([$category->id]);

        $product = Product::create([
            'outlet_id' => $sourceOutlet->id,
            'name' => 'Kue Lapis',
            'price' => 20000,
            'cost_price' => 12000,
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        $recipe = ProductRecipe::create([
            'product_id' => $product->id,
            'yield_qty' => 1,
            'unit' => 'pcs',
        ]);

        ProductRecipeItem::create([
            'product_recipe_id' => $recipe->id,
            'raw_material_id' => $rawMaterial->id,
            'qty_per_yield' => 0.5,
            'waste_pct' => 0,
        ]);

        /** @var CatalogDuplicationService $service */
        $service = app(CatalogDuplicationService::class);

        $job = $service->createJob(
            $user,
            $sourceOutlet,
            $targetOutlet,
            [
                'categories' => true,
                'raw_materials' => true,
                'products' => true,
                'category_ids' => [$category->id],
                'raw_material_ids' => [$rawMaterial->id],
                'product_ids' => [$product->id],
            ],
            ['copy_stock' => true]
        );

        $job = $service->runJob($job);

        Notification::assertSentTo($user, DuplicationJobFinished::class);

        $this->assertEquals(DuplicationJob::STATUS_COMPLETED, $job->status);
        $this->assertEquals(1, $job->counts['categories']);
        $this->assertEquals(1, $job->counts['raw_materials']);
        $this->assertEquals(1, $job->counts['products']);

        $this->assertDatabaseHas('duplication_job_items', [
            'duplication_job_id' => $job->id,
            'entity_type' => 'category',
            'status' => 'completed',
        ]);

        $duplicatedCategoryId = Category::query()
            ->forOutlet($targetOutlet->id)
            ->where('name', 'Kue')
            ->value('id');

        $duplicatedRawMaterial = RawMaterial::query()
            ->forOutlet($targetOutlet->id)
            ->where('name', 'Gula')
            ->first();

        $this->assertNotNull($duplicatedCategoryId);
        $this->assertNotNull($duplicatedRawMaterial);
        $this->assertEquals(5.0, (float) $duplicatedRawMaterial->stock_qty);

        $duplicatedProduct = Product::query()
            ->forOutlet($targetOutlet->id)
            ->where('name', 'Kue Lapis')
            ->with('recipe.items')
            ->first();

        $this->assertNotNull($duplicatedProduct);
        $this->assertEquals($duplicatedCategoryId, $duplicatedProduct->category_id);
        $this->assertNotNull($duplicatedProduct->recipe);
        $this->assertCount(1, $duplicatedProduct->recipe->items);
        $this->assertEquals($duplicatedRawMaterial->id, $duplicatedProduct->recipe->items->first()->raw_material_id);
    }
}
