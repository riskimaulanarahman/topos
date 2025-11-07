<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\RawMaterial;
use App\Services\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateExpenseWithRawMaterialItemsAdjustsStock(): void
    {
        $service = app(ExpenseService::class);
        $material = RawMaterial::create([
            'sku' => 'RM-001',
            'name' => 'Gula',
            'unit' => 'kg',
            'unit_cost' => 0,
            'stock_qty' => 0,
            'min_stock' => 0,
        ]);

        $expense = $service->create([
            'date' => '2025-01-05',
            'reference_no' => 'EXP-20250105-0001',
            'vendor' => 'Supplier A',
            'notes' => 'Pembelian gula'
        ], [[
            'raw_material_id' => $material->id,
            'description' => null,
            'unit' => null,
            'qty' => 5,
            'item_price' => 50000,
            'notes' => null,
        ]]);

        $this->assertInstanceOf(Expense::class, $expense);
        $this->assertCount(1, $expense->items);

        $item = $expense->items->first();
        $this->assertInstanceOf(ExpenseItem::class, $item);
        $this->assertSame($material->id, $item->raw_material_id);
        $this->assertSame(5.0, (float) $item->qty);
        $this->assertSame(10000.0, (float) $item->unit_cost);
        $this->assertEquals(50000.0, (float) $expense->amount);

        $material->refresh();
        $this->assertSame(5.0, (float) $material->stock_qty);
        $this->assertSame(10000.0, (float) $material->unit_cost);
    }

    public function testUpdateExpenseRecomputesStock(): void
    {
        $service = app(ExpenseService::class);
        $material = RawMaterial::create([
            'sku' => 'RM-002',
            'name' => 'Susu',
            'unit' => 'liter',
            'unit_cost' => 0,
            'stock_qty' => 0,
            'min_stock' => 0,
        ]);

        $expense = $service->create([
            'date' => '2025-01-05',
            'reference_no' => 'EXP-20250105-0002',
        ], [[
            'raw_material_id' => $material->id,
            'qty' => 10,
            'item_price' => 200000,
        ]]);

        $service->update($expense, [
            'date' => '2025-01-06',
        ], [[
            'raw_material_id' => $material->id,
            'qty' => 6,
            'item_price' => 132000,
        ]]);

        $expense->refresh();
        $material->refresh();

        $this->assertEquals(6.0, (float) $material->stock_qty);
        $this->assertEquals(22000.0, (float) $material->unit_cost);
        $this->assertEquals(132000.0, (float) $expense->amount);
    }

    public function testDeleteExpenseRollsBackStock(): void
    {
        $service = app(ExpenseService::class);
        $material = RawMaterial::create([
            'sku' => 'RM-003',
            'name' => 'Kopi',
            'unit' => 'kg',
            'unit_cost' => 0,
            'stock_qty' => 0,
            'min_stock' => 0,
        ]);

        $expense = $service->create([
            'date' => '2025-01-05',
            'reference_no' => 'EXP-20250105-0003',
        ], [[
            'raw_material_id' => $material->id,
            'qty' => 4,
            'item_price' => 200000,
        ]]);

        $this->assertEquals(4.0, (float) $material->fresh()->stock_qty);

        $service->delete($expense);

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
        $this->assertEquals(0.0, (float) $material->fresh()->stock_qty);
    }
}
