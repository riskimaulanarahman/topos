<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\ReportDateRange;
use App\Support\OutletContext;
use App\Services\PartnerCategoryAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SummaryController extends Controller
{
    public function index()
    {
        $context = $this->resolveOutletContext(null);
        $ownerUserIds = $context['owner_user_ids'];
        $accessibleCategoryIds = $context['accessible_category_ids'];
        $isAdmin = $context['is_admin'];
        $hasCategoryColumn = $this->hasProductCategoryColumn();

        $paymentMethods = Order::whereIn('user_id', $ownerUserIds)->select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed','refund','pending'];
        $categories = $hasCategoryColumn
            ? Category::whereIn('user_id', $ownerUserIds)
                ->when($this->shouldFilterCategories($accessibleCategoryIds), fn($q) => $q->whereIn('id', $accessibleCategoryIds))
                ->when(($context['is_partner'] ?? false) && empty($accessibleCategoryIds), fn($q) => $q->whereRaw('0 = 1'))
                ->orderBy('name')->get(['id','name'])
            : collect();
        $products = Product::whereIn('user_id', $ownerUserIds)
            ->when($this->shouldFilterCategories($accessibleCategoryIds), fn($q) => $q->whereIn('category_id', $accessibleCategoryIds))
            ->when(($context['is_partner'] ?? false) && empty($accessibleCategoryIds), fn($q) => $q->whereRaw('0 = 1'))
            ->orderBy('name')->get(['id','name']);
        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::whereIn('id', $ownerUserIds)->get(['id','name']);
        return view('pages.summary.index', compact('paymentMethods','statuses','categories','products','users'));
    }

    public function filterSummary(Request $request)
    {
        // Resolve date range from new period filters (fallback to old behavior)
        $resolved = ReportDateRange::fromRequest($request);
        if (!$resolved['from'] || !$resolved['to']) {
            $this->validate($request, [
                'date_from'  => 'required|date',
                'date_to'    => 'required|date|after_or_equal:date_from',
            ]);
        }

        $date_from  = $resolved['from'] ?? $request->date_from;
        $date_to    = $resolved['to'] ?? $request->date_to;
        $status = $request->input('status');
        $paymentMethod = $request->input('payment_method');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');
        $context = $this->resolveOutletContext($request->input('user_id'));
        $ownerUserIds = $context['owner_user_ids'];
        $accessibleCategoryIds = $context['accessible_category_ids'];
        $isAdmin = $context['is_admin'];
        $hasCategoryColumn = $this->hasProductCategoryColumn();
        $period = $request->input('period');
        $year = $request->input('year');
        $month = $request->input('month');
        $weekInMonth = $request->input('week_in_month');
        $lastDays = $request->input('last_days');

        $query = Order::query()
            ->whereDate('created_at', '>=', $date_from)
            ->whereDate('created_at', '<=', $date_to)
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($paymentMethod, fn($q) => $q->where('payment_method', $paymentMethod))
            ->whereIn('user_id', $ownerUserIds)
            ->when(($context['is_partner'] ?? false) && $hasCategoryColumn && empty($accessibleCategoryIds), function ($q) {
                $q->whereRaw('0 = 1');
            })
            ->when($hasCategoryColumn && $categoryId, function ($q) use ($categoryId) {
                $q->whereExists(function ($sub) use ($categoryId) {
                    $sub->select(DB::raw(1))
                        ->from('order_items')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where('products.category_id', $categoryId);
                });
            })
            ->when($productId, function ($q) use ($productId) {
                $q->whereExists(function ($sub) use ($productId) {
                    $sub->select(DB::raw(1))
                        ->from('order_items')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where('order_items.product_id', $productId);
                });
            })
            ->when($this->shouldFilterCategories($accessibleCategoryIds), function ($q) use ($accessibleCategoryIds) {
                $q->whereExists(function ($sub) use ($accessibleCategoryIds) {
                    $sub->select(DB::raw(1))
                        ->from('order_items')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->whereIn('products.category_id', $accessibleCategoryIds);
                });
            });

        // Revenue & metrics
        $totalRevenue = (clone $query)->sum('total_price');
        $totalDiscount = (clone $query)->sum('discount_amount');
        $totalTax = (clone $query)->sum('tax');
        $totalServiceCharge = (clone $query)->sum('service_charge');
        $totalSubtotal = (clone $query)->sum('sub_total');
        $total = $totalSubtotal - $totalDiscount + $totalTax + $totalServiceCharge;

        // Trend: respect period
        $selectExpr = DB::raw('DATE(created_at) as bucket');
        $groupExpr = DB::raw('DATE(created_at)');
        if ($period === 'mingguan') {
            $selectExpr = DB::raw('YEARWEEK(created_at, 3) as bucket');
            $groupExpr = DB::raw('YEARWEEK(created_at, 3)');
        } elseif ($period === 'bulanan') {
            $selectExpr = DB::raw("DATE_FORMAT(created_at, '%Y-%m') as bucket");
            $groupExpr = DB::raw("DATE_FORMAT(created_at, '%Y-%m')");
        } elseif ($period === 'tahunan') {
            $selectExpr = DB::raw('YEAR(created_at) as bucket');
            $groupExpr = DB::raw('YEAR(created_at)');
        }

        $rows = (clone $query)
            ->select([$selectExpr, DB::raw('SUM(total_price) as revenue')])
            ->groupBy($groupExpr)
            ->orderBy('bucket')
            ->get();
        $labels = $rows->pluck('bucket')->map(function ($b) use ($period) {
            if ($period === 'mingguan') {
                $str = (string)$b; $year = substr($str, 0, 4); $week = substr($str, -2);
                return $year . ' W' . $week;
            }
            return (string)$b;
        });
        if (!$period || $period === 'harian') {
            $labels = $labels->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));
        }
        $chartTrend = [
            'labels' => $labels,
            'revenue' => $rows->pluck('revenue'),
        ];

        $composition = [
            'labels' => ['Subtotal', 'Discount', 'Tax', 'Service Charge'],
            'values' => [
                $totalSubtotal,
                $totalDiscount,
                $totalTax,
                $totalServiceCharge,
            ],
        ];

        $paymentMethods = Order::whereIn('user_id', $ownerUserIds)->select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed','refund','pending'];
        $categories = $hasCategoryColumn
            ? Category::whereIn('user_id', $ownerUserIds)
                ->when($this->shouldFilterCategories($accessibleCategoryIds), fn($q) => $q->whereIn('id', $accessibleCategoryIds))
                ->orderBy('name')->get(['id','name'])
            : collect();
        $products = Product::whereIn('user_id', $ownerUserIds)
            ->when($this->shouldFilterCategories($accessibleCategoryIds), fn($q) => $q->whereIn('category_id', $accessibleCategoryIds))
            ->orderBy('name')->get(['id','name']);
        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::whereIn('id', $ownerUserIds)->get(['id','name']);

        return view('pages.summary.index', compact(
            'totalRevenue', 'totalDiscount', 'totalTax', 'totalServiceCharge', 'totalSubtotal', 'total',
            'chartTrend', 'composition', 'date_from', 'date_to', 'paymentMethods','statuses','categories','products',
            'status','paymentMethod','categoryId','productId','period','year','month','weekInMonth','lastDays','users'
        ));
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
        if ($currentRole && $currentRole->role === 'partner' && $activeOutlet) {
            /** @var PartnerCategoryAccessService $access */
            $access = app(PartnerCategoryAccessService::class);
            $cats = $access->accessibleCategoryIdsFor($user, $activeOutlet);
            $accessibleCategoryIds = $cats === ['*'] ? ['*'] : (array) $cats;
        }

        $isPartner = $currentRole && $currentRole->role === 'partner';

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
