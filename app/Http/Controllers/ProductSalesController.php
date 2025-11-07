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

    public function index()
    {
        $context = $this->resolveOutletContext(null);
        $ownerUserIds = $context['owner_user_ids'];
        $accessibleCategoryIds = $context['accessible_category_ids'];
        $isAdmin = $context['is_admin'];
        $hasCategoryColumn = $this->hasProductCategoryColumn();

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
        return view('pages.product_sales.index', compact('categories','products','users'));
    }


    public function productSales(Request $request)
    {
        $resolved = ReportDateRange::fromRequest($request);
        if (!$resolved['from'] || !$resolved['to']) {
            $this->validate($request, [
                'date_from' => 'required|date',
                'date_to'   => 'required|date|after_or_equal:date_from',
            ]);
        }

        $date_from  = $resolved['from'] ?? $request->date_from;
        $date_to    = $resolved['to'] ?? $request->date_to;
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');
        $context = $this->resolveOutletContext($request->input('user_id'));
        $ownerUserIds = $context['owner_user_ids'];
        $accessibleCategoryIds = $context['accessible_category_ids'];
        $isAdmin = $context['is_admin'];
        $hasCategoryColumn = $this->hasProductCategoryColumn();
        $year = $request->input('year');
        $month = $request->input('month');
        $weekInMonth = $request->input('week_in_month');
        $lastDays = $request->input('last_days');

        $query = OrderItem::select(
            'products.id as product_id',
            'products.name as product_name',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.total_price) as total_price')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$date_from, $date_to])
            ->whereIn('orders.user_id', $ownerUserIds)
            ->when(($context['is_partner'] ?? false) && $hasCategoryColumn && empty($accessibleCategoryIds), fn($q) => $q->whereRaw('0 = 1'))
            ->when($hasCategoryColumn && $categoryId, fn($q) => $q->where('products.category_id', $categoryId))
            ->when($this->shouldFilterCategories($accessibleCategoryIds), fn($q) => $q->whereIn('products.category_id', $accessibleCategoryIds))
            ->when($productId, fn($q) => $q->where('order_items.product_id', $productId))
            ->groupBy('products.id','products.name')
            ->orderBy('total_quantity', 'desc');

        $totalProductSold = $query->get();

        $chart = [
            'labels' => $totalProductSold->pluck('product_name'),
            'quantity' => $totalProductSold->pluck('total_quantity'),
            'revenue' => $totalProductSold->pluck('total_price'),
        ];

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

        return view('pages.product_sales.index', compact('totalProductSold', 'chart', 'date_from', 'date_to','categories','products','categoryId','productId','year','month','weekInMonth','lastDays','users'));
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
