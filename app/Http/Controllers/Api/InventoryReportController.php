<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class InventoryReportController extends Controller
{
    public function summary()
    {
        Gate::authorize('inventory.manage');

        $user = Auth::user();
        $baseQuery = RawMaterial::query()->accessibleBy($user);

        $totalMaterials = (clone $baseQuery)->count();
        $lowStockCount = (clone $baseQuery)->whereColumn('stock_qty','<=','min_stock')->count();
        $valuation = (float) (clone $baseQuery)->sum(\DB::raw('stock_qty * unit_cost'));

        // top 10 low-stock items for quick glance
        $items = (clone $baseQuery)
            ->whereColumn('stock_qty','<=','min_stock')
            ->orderBy('name')
            ->take(10)
            ->get(['id','sku','name','stock_qty','min_stock'])
            ->map(fn($m) => [
                'id' => $m->id,
                'sku' => $m->sku,
                'name' => $m->name,
                'stock_qty' => (float) $m->stock_qty,
                'min_stock' => (float) $m->min_stock,
            ]);

        return response()->json([
            'data' => [
                'total_materials' => $totalMaterials,
                'low_stock_count' => $lowStockCount,
                'inventory_valuation' => round($valuation, 2),
                'low_stock_preview' => $items,
            ]
        ]);
    }
}
