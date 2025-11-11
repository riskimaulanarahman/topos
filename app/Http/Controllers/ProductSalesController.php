<?php

namespace App\Http\Controllers;

use App\Exports\ProductSalesExport;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\ReportDateRange;
use App\Support\OutletContext;
use App\Services\PartnerCategoryAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductSalesController extends Controller
{
    /**
     * Display product sales report page
     */
    public function index(Request $request)
    {
        $context = $this->resolveOutletContext($request->input('user_id'));
        $ownerUserIds = $context['owner_user_ids'];
        $accessibleCategoryIds = $context['accessible_category_ids'];
        $isAdmin = $context['is_admin'];
        $hasCategoryColumn = $this->hasProductCategoryColumn();

        // Get outlets for filter dropdown
        $outlets = \App\Models\Outlet::when(!$isAdmin, fn($q) => $q->whereHas('owners', fn($subQ) => $subQ->whereIn('users.id', $ownerUserIds)))
            ->orderBy('name')
            ->select(['id', 'name'])
            ->get();

        // Optimized queries with eager loading and specific column selection
        $categories = $hasCategoryColumn
            ? Category::whereIn('user_id', $ownerUserIds)
                ->when($this->shouldFilterCategories($accessibleCategoryIds), fn($q) => $q->whereIn('id', $accessibleCategoryIds))
                ->when(($context['is_partner'] ?? false) && empty($accessibleCategoryIds), fn($q) => $q->whereRaw('0 = 1'))
                ->orderBy('name')
                ->select(['id','name'])
                ->get()
            : collect();
        
        $products = Product::whereIn('user_id', $ownerUserIds)
            ->when($this->shouldFilterCategories($accessibleCategoryIds), fn($q) => $q->whereIn('category_id', $accessibleCategoryIds))
            ->orderBy('name')
            ->select(['id','name'])
            ->get();
            
        $users = $isAdmin
            ? User::orderBy('name')->select(['id','name'])->get()
            : User::whereIn('id', $ownerUserIds)->select(['id','name'])->get();

        // Handle filter request
        if ($request->hasAny(['date_from', 'date_to', 'category_id', 'product_id', 'outlet_id'])) {
            return $this->filter($request);
        }

        return view('pages.product_sales.index', compact('categories','products','users','outlets'));
    }

    /**
     * Handle filter requests for product sales
     */
    public function filter(Request $request)
    {
        // Compute date range based on new period filters. Fallback to old validation if needed.
        $resolved = ReportDateRange::fromRequest($request);
        if (!$resolved['from'] || !$resolved['to']) {
            // Default last 30 days if not provided
            $request->merge([
                'date_from' => now()->copy()->subDays(29)->toDateString(),
                'date_to' => now()->toDateString(),
            ]);
        }

        $date_from  = $resolved['from'] ?? $request->date_from;
        $date_to    = $resolved['to'] ?? $request->date_to;
        
        // Force completed status for this report
        $status = 'completed';
        
        // Allow multi-select for categories
        $categoryIds = (array) $request->input('category_id', []);
        $productId = $request->input('product_id');
        $outletId = $request->input('outlet_id');
        $requestedUser = $request->input('user_id');
        
        // Period handling variables
        $period = $request->input('period');
        $year = $request->input('year');
        $month = $request->input('month');
        $weekInMonth = $request->input('week_in_month');
        $lastDays = $request->input('last_days');
        
        $context = $this->resolveOutletContext($requestedUser);
        $ownerUserIds = $context['owner_user_ids'];
        $accessibleCategoryIds = $context['accessible_category_ids'];
        $isAdmin = $context['is_admin'];
        $hasCategoryColumn = $this->hasProductCategoryColumn();
        $selectedUserId = $requestedUser ?: ($ownerUserIds[0] ?? auth()->id());

        $query = OrderItem::select(
            'products.id as product_id',
            'products.name as product_name',
            'products.cost_price',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.total_price) as total_price'),
            DB::raw('SUM(order_items.total_price) - SUM(order_items.quantity * IFNULL(products.cost_price, 0)) as total_profit'),
            DB::raw('COUNT(DISTINCT order_items.order_id) as order_count'),
            DB::raw('AVG(order_items.total_price / order_items.quantity) as avg_unit_price')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$date_from, $date_to])
            ->whereIn('orders.user_id', $ownerUserIds)
            ->when($status, function($q) use ($status){
                if (is_array($status)) { $q->whereIn('orders.status', $status); }
                else { $q->where('orders.status', $status); }
            })
            ->when($this->shouldFilterCategories($accessibleCategoryIds), function ($q) use ($accessibleCategoryIds) {
                $q->whereHas('product', function ($query) use ($accessibleCategoryIds) {
                    $query->whereIn('products.category_id', $accessibleCategoryIds);
                });
            })
            ->when(!empty($categoryIds), function ($q) use ($categoryIds) {
                $q->whereHas('product', function ($query) use ($categoryIds) {
                    $query->whereIn('products.category_id', $categoryIds);
                });
            })
            ->when($productId, fn($q) => $q->where('order_items.product_id', $productId))
            ->when($outletId, fn($q) => $q->where('orders.outlet_id', $outletId))
            ->groupBy('products.id','products.name','products.cost_price')
            ->orderByDesc('total_quantity');

        $totalProductSold = $query->get();
        
        // Calculate additional metrics
        $totalRevenue = $totalProductSold->sum('total_price');
        $totalCost = $totalProductSold->sum(function($item) {
            return $item->total_quantity * ($item->cost_price ?? 0);
        });
        $totalProfit = $totalRevenue - $totalCost;
        $avgProfitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        
        // Simplified data without performance index
        $totalProductSold = $totalProductSold->map(function($item) use ($totalRevenue) {
            $profitMargin = $item->total_price > 0 ? (($item->total_price - ($item->total_quantity * ($item->cost_price ?? 0))) / $item->total_price) * 100 : 0;
            
            $item->profit_margin = round($profitMargin, 2);
            $item->avg_unit_price = round($item->avg_unit_price);
            $item->order_count = $item->order_count;
            
            return $item;
        });

        $chart = [
            'labels' => $totalProductSold->pluck('product_name'),
            'quantity' => $totalProductSold->pluck('total_quantity'),
            'revenue' => $totalProductSold->pluck('total_price'),
            'profit' => $totalProductSold->pluck('total_profit'),
        ];

        // Get outlets for filter dropdown
        $outlets = \App\Models\Outlet::when(!$isAdmin, fn($q) => $q->whereHas('owners', fn($subQ) => $subQ->whereIn('users.id', $ownerUserIds)))
            ->orderBy('name')
            ->select(['id', 'name'])
            ->get();

        $categories = $hasCategoryColumn
            ? Category::whereIn('user_id', $ownerUserIds)
                ->when($this->shouldFilterCategories($accessibleCategoryIds), fn($q) => $q->whereIn('id', $accessibleCategoryIds))
                ->when(($context['is_partner'] ?? false) && empty($accessibleCategoryIds), fn($q) => $q->whereRaw('0 = 1'))
                ->orderBy('name')->get(['id','name'])
            : collect();
        $products = Product::whereIn('user_id', $ownerUserIds)
            ->when($this->shouldFilterCategories($accessibleCategoryIds), fn($q) => $q->whereIn('category_id', $accessibleCategoryIds))
            ->orderBy('name')->get(['id','name']);
        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::whereIn('id', $ownerUserIds)->get(['id','name']);

        // Prepare view data correctly
        $viewData = [
            'totalProductSold' => $totalProductSold,
            'chart' => $chart,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'categories' => $categories,
            'products' => $products,
            'categoryId' => $categoryIds,
            'productId' => $productId,
            'outletId' => $outletId,
            'outlets' => $outlets,
            'users' => $users,
            'totalRevenue' => $totalRevenue,
            'totalProfit' => $totalProfit,
            'avgProfitMargin' => $avgProfitMargin,
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'weekInMonth' => $weekInMonth,
            'lastDays' => $lastDays,
            'userId' => $selectedUserId,
        ];

        return view('pages.product_sales.index', $viewData);
    }

    public function download(Request $request)
    {
        $this->validate($request, [
            'date_from'  => 'required',
            'date_to'    => 'required',
        ]);

        $date_from  = $request->date_from;
        $date_to    = $request->date_to;
        $context = $this->resolveOutletContext($request->input('user_id'));
        $ownerUserIds = $context['owner_user_ids'];
        $accessibleCategoryIds = $context['accessible_category_ids'];
        $isPartner = $context['is_partner'];

        if ($isPartner && $this->hasProductCategoryColumn() && empty($accessibleCategoryIds)) {
            return back()->with('error', 'Owner outlet belum membagikan kategori kepada Anda. Ajukan permintaan terlebih dahulu.');
        }

        return (new ProductSalesExport)
            ->forRange($date_from, $date_to)
            ->withUsers($ownerUserIds)
            ->withCategoryFilter($accessibleCategoryIds)
            ->download('Product-Sales.csv');
    }

    private function resolveOutletContext($requestedUserId = null): array
    {
        $user = auth()->user();
        $isAdmin = $user?->roles === 'admin';

        $activeOutlet = OutletContext::currentOutlet();
        $currentRole = OutletContext::currentRole();

        $ownerUserIds = [];
        if ($activeOutlet) {
            $ownerUserIds = $activeOutlet->owners()->pluck('users.id')->unique()->values()->all();
        }
        if (empty($ownerUserIds)) {
            $ownerUserIds = [$requestedUserId ?: $user?->id];
        }

        $accessibleCategoryIds = [];
        $isPartner = $currentRole && $currentRole->role === 'partner';
        if ($isPartner && $activeOutlet) {
            /** @var PartnerCategoryAccessService $access */
            $access = app(PartnerCategoryAccessService::class);
            $cats = $access->accessibleCategoryIdsFor($user, $activeOutlet);
            $accessibleCategoryIds = $cats === ['*'] ? ['*'] : (array) $cats;
        }

        return [
            'is_admin' => $isAdmin,
            'owner_user_ids' => $ownerUserIds,
            'accessible_category_ids' => $accessibleCategoryIds,
            'is_partner' => $isPartner,
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
}
