<?php

namespace App\Http\Controllers;

use App\Models\CashierSession;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\OutletUserRole;
use App\Models\RawMaterial;
use App\Models\User;
use App\Services\CashierSummaryService;
use App\Services\PartnerCategoryAccessService;
use App\Support\OutletContext;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $outletContext = $this->resolveOutletSnapshot();
        $activeOutlet = $outletContext['activeOutlet'] ?? null;
        $activeOutletId = $activeOutlet?->id;
        $ownerUserIds = $outletContext['ownerUserIds'] ?? [$userId];
        $primaryUserId = $ownerUserIds[0] ?? $userId;

        $users = $activeOutletId
            ? OutletUserRole::where('outlet_id', $activeOutletId)->where('status', 'active')->count()
            : User::whereIn('id', $ownerUserIds)->count();

        $products = Product::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->count();

        $ordersLength = Order::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->count();

        $categories = Category::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->count();

        [$rangeStart, $rangeEnd, $activeSession] = $this->resolveActiveSessionRange($primaryUserId, $activeOutletId);

        $orders = Order::with('user')
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->orderBy('created_at', 'DESC')
            ->paginate(10, ['*'], 'orders_page');
        $orders = $this->appendTransactionTimeMeta($orders);

        $totalPriceToday = Order::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->sum('total_price');

        // Breakdown revenue today by payment method
        $paymentBreakdownToday = Order::select('payment_method', DB::raw('SUM(total_price) as total_revenue'))
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->groupBy('payment_method')
            ->orderByDesc(DB::raw('SUM(total_price)'))
            ->get();

        // Produk terjual hari ini (nama produk dan jumlah)
        $productSalesToday = OrderItem::select(
                'products.name as product_name',
                DB::raw('SUM(order_items.quantity) as total_quantity')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->when($activeOutletId, fn ($query) => $query->where('orders.outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('orders.user_id', $ownerUserIds))
            ->whereBetween('orders.created_at', [$rangeStart, $rangeEnd])
            ->groupBy('products.name')
            ->orderByDesc('total_quantity')
            ->paginate(10, ['*'], 'products_page');

        $month = date('m');
        $year = date('Y');

        $data = $this->getMonthlyData($month, $year, $ownerUserIds, $activeOutletId);
        $cashierSessionSummaries = $this->getCashierSessionSummaries($primaryUserId, $activeOutletId);
        $sessionRange = $this->formatSessionRangeForView($rangeStart, $rangeEnd, $activeSession);

        // Monthly summary for completed orders (current month)
        $monthlyCompletedOrders = Order::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('status', 'completed')
            ->count();
        $monthlyCompletedRevenue = Order::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('status', 'completed')
            ->sum('total_price');
        $monthlyAov = $monthlyCompletedOrders > 0 ? round($monthlyCompletedRevenue / $monthlyCompletedOrders) : 0;
        $monthlyPaymentMethods = Order::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('status', 'completed')
            ->distinct('payment_method')
            ->count('payment_method');
        [$rawMaterialStocks, $rawMaterialStockSummary] = $this->getRawMaterialStockSnapshot($ownerUserIds, $activeOutletId);
        $partnerChart = $this->buildPartnerChart($outletContext, $ownerUserIds, $activeOutletId);
        $partnerMonthlyOmzet = $this->calculatePartnerMonthlyOmzet($outletContext, $ownerUserIds, $activeOutletId, (int) $month, (int) $year);
        $partnerSummary = $this->buildPartnerSummary($outletContext, $partnerMonthlyOmzet, $monthlyCompletedOrders, $monthlyAov);

        return view('pages.dashboard', array_merge($outletContext, compact(
            'users',
            'products',
            'ordersLength',
            'categories',
            'orders',
            'totalPriceToday',
            'productSalesToday',
            'paymentBreakdownToday',
            'data',
            'month',
            'year',
            'monthlyCompletedOrders',
            'monthlyCompletedRevenue',
            'monthlyAov',
            'monthlyPaymentMethods',
            'cashierSessionSummaries',
            'sessionRange',
            'activeSession',
            'rawMaterialStocks',
            'rawMaterialStockSummary'
        ) + [
            'partnerChart' => $partnerChart,
            'partnerMonthlyOmzet' => $partnerMonthlyOmzet,
            'partnerSummary' => $partnerSummary,
        ]));
    }

    public function filter(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
        ]);

        $month = $request->input('month');
        $year = $request->input('year');
        $userId = auth()->id();

        $outletContext = $this->resolveOutletSnapshot();
        $activeOutlet = $outletContext['activeOutlet'] ?? null;
        $activeOutletId = $activeOutlet?->id;
        $ownerUserIds = $outletContext['ownerUserIds'] ?? [$userId];
        $primaryUserId = $ownerUserIds[0] ?? $userId;

        $users = $activeOutletId
            ? OutletUserRole::where('outlet_id', $activeOutletId)->where('status', 'active')->count()
            : User::whereIn('id', $ownerUserIds)->count();

        $products = Product::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->count();

        $ordersLength = Order::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->count();

        $categories = Category::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->count();

        [$rangeStart, $rangeEnd, $activeSession] = $this->resolveActiveSessionRange($primaryUserId, $activeOutletId);

        $orders = Order::with('user')
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->orderBy('created_at', 'DESC')
            ->paginate(10, ['*'], 'orders_page');
        $orders = $this->appendTransactionTimeMeta($orders);

        $totalPriceToday = Order::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->sum('total_price');

        // Breakdown revenue today by payment method
        $paymentBreakdownToday = Order::select('payment_method', DB::raw('SUM(total_price) as total_revenue'))
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->groupBy('payment_method')
            ->orderByDesc(DB::raw('SUM(total_price)'))
            ->get();

        // Produk terjual hari ini (nama produk dan jumlah) untuk halaman filter
        $productSalesToday = OrderItem::select(
                'products.name as product_name',
                DB::raw('SUM(order_items.quantity) as total_quantity')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->when($activeOutletId, fn ($query) => $query->where('orders.outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('orders.user_id', $ownerUserIds))
            ->whereBetween('orders.created_at', [$rangeStart, $rangeEnd])
            ->groupBy('products.name')
            ->orderByDesc('total_quantity')
            ->paginate(10, ['*'], 'products_page');

        $data = $this->getMonthlyData($month, $year, $ownerUserIds, $activeOutletId);
        $cashierSessionSummaries = $this->getCashierSessionSummaries($primaryUserId, $activeOutletId);
        $sessionRange = $this->formatSessionRangeForView($rangeStart, $rangeEnd, $activeSession);
        $monthlyCompletedOrders = Order::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('status', 'completed')
            ->count();
        $monthlyCompletedRevenue = Order::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('status', 'completed')
            ->sum('total_price');
        $monthlyAov = $monthlyCompletedOrders > 0 ? round($monthlyCompletedRevenue / $monthlyCompletedOrders) : 0;
        $monthlyPaymentMethods = Order::query()
            ->when($activeOutletId, fn ($query) => $query->where('outlet_id', $activeOutletId))
            ->when(! $activeOutletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('status', 'completed')
            ->distinct('payment_method')
            ->count('payment_method');
        [$rawMaterialStocks, $rawMaterialStockSummary] = $this->getRawMaterialStockSnapshot($ownerUserIds, $activeOutletId);
        $partnerChart = $this->buildPartnerChart($outletContext, $ownerUserIds, $activeOutletId);
        $partnerMonthlyOmzet = $this->calculatePartnerMonthlyOmzet($outletContext, $ownerUserIds, $activeOutletId, (int) $month, (int) $year);
        $partnerSummary = $this->buildPartnerSummary($outletContext, $partnerMonthlyOmzet, $monthlyCompletedOrders, $monthlyAov);

        return view('pages.dashboard', array_merge($outletContext, compact(
            'users',
            'products',
            'ordersLength',
            'categories',
            'orders',
            'totalPriceToday',
            'productSalesToday',
            'paymentBreakdownToday',
            'data',
            'month',
            'year',
            'monthlyCompletedOrders',
            'monthlyCompletedRevenue',
            'monthlyAov',
            'monthlyPaymentMethods',
            'cashierSessionSummaries',
            'sessionRange',
            'activeSession',
            'rawMaterialStocks',
            'rawMaterialStockSummary'
        ) + [
            'partnerChart' => $partnerChart,
            'partnerMonthlyOmzet' => $partnerMonthlyOmzet,
            'partnerSummary' => $partnerSummary,
        ]));
    }

    private function getMonthlyData($month, $year, array $userIds, ?int $outletId = null)
    {
        $daysInMonth = Carbon::createFromDate($year, $month)->daysInMonth;

        $dailyData = array_fill(1, $daysInMonth, 0);

        $totalPriceDaily = Order::selectRaw('DAY(created_at) as day, SUM(total_price) as total_price')
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId))
            ->when(! $outletId, fn ($query) => $query->whereIn('user_id', $userIds))
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->groupByRaw('DAY(created_at)')
            ->get();

        foreach ($totalPriceDaily as $data) {
            $dailyData[$data->day] = $data->total_price;
        }

        return $dailyData;
    }

    private function getRawMaterialStockSnapshot(array $userIds, ?int $outletId, int $limit = 10): array
    {
        $baseQuery = RawMaterial::query()
            ->select(['id', 'name', 'unit', 'stock_qty', 'min_stock', 'created_by']);

        $user = auth()->user();
        if ($outletId) {
            $baseQuery->where('outlet_id', $outletId);
        } elseif (! $user || $user->roles !== 'admin') {
            $baseQuery->where(function ($query) use ($userIds) {
                $query->whereIn('created_by', $userIds)
                    ->orWhereNull('created_by');
            });
        }

        $orderedQuery = (clone $baseQuery)
            ->orderByRaw('CASE WHEN min_stock IS NULL OR min_stock = 0 THEN 2 ELSE stock_qty / NULLIF(min_stock, 0) END ASC')
            ->orderBy('name');

        $items = $orderedQuery->take($limit)->get();

        $lowStockCount = (clone $baseQuery)
            ->whereNotNull('min_stock')
            ->where('min_stock', '>', 0)
            ->whereColumn('stock_qty', '<=', 'min_stock')
            ->count();

        $totalCount = (clone $baseQuery)->count();

        return [$items, [
            'total' => $totalCount,
            'low' => $lowStockCount,
        ]];
    }

    private function appendTransactionTimeMeta($orders)
    {
        $collection = $orders->getCollection();
        $collection->transform(function ($order) {
            $transactionTime = $order->transaction_time;

            if ($transactionTime instanceof CarbonInterface) {
                $iso = $transactionTime->toIso8601String();
                $fallback = $transactionTime->toDateTimeString();
            } elseif ($transactionTime) {
                try {
                    $carbon = Carbon::parse($transactionTime, config('app.timezone'));
                    $iso = $carbon->toIso8601String();
                    $fallback = $carbon->toDateTimeString();
                } catch (\Throwable $e) {
                    $iso = null;
                    $fallback = is_scalar($transactionTime) ? (string) $transactionTime : null;
                }
            } else {
                $iso = null;
                $fallback = null;
            }

            $order->transaction_time_iso = $iso;
            $order->transaction_time_display = $fallback ?? '-';

            return $order;
        });

        return $orders->setCollection($collection);
    }

    private function resolveActiveSessionRange(int $userId, ?int $outletId = null): array
    {
        $session = null;

        $baseQuery = fn () => CashierSession::where('user_id', $userId)->latest('opened_at');

        if ($outletId) {
            $openSession = (clone $baseQuery())->where('outlet_id', $outletId)->where('status', 'open')->first();
            $session = $openSession ?: (clone $baseQuery())->where('outlet_id', $outletId)->first();

            if (! $session) {
                $session = (clone $baseQuery())->whereNull('outlet_id')->first();
            }
        } else {
            $session = (clone $baseQuery())->where('status', 'open')->first() ?? $baseQuery()->first();
        }

        if ($session) {
            $start = $session->opened_at ?? $session->created_at ?? now()->startOfDay();
            $end = $session->closed_at ?? now();

            return [$start->copy(), $end->copy(), $session];
        }

        $todayStart = now()->startOfDay();

        return [$todayStart->copy(), now(), null];
    }

    private function formatSessionRangeForView(Carbon $start, Carbon $end, ?CashierSession $session): array
    {
        $timezone = config('app.timezone', 'UTC');

        $startLocal = $start->copy()->setTimezone($timezone);
        $endLocal = $end->copy()->setTimezone($timezone);

        return [
            'start' => $startLocal->format('Y-m-d H:i:s'),
            'end' => $endLocal->format('Y-m-d H:i:s'),
            'start_iso' => $startLocal->toIso8601String(),
            'end_iso' => $endLocal->toIso8601String(),
            'hasSession' => (bool) $session,
            'sessionId' => $session?->id,
            'status' => $session?->status,
        ];
    }

    private function getCashierSessionSummaries(int $userId, ?int $outletId = null, int $limit = 5)
    {
        $summaryService = app(CashierSummaryService::class);

        $query = CashierSession::with(['openedBy:id,name', 'closedBy:id,name'])
            ->where('user_id', $userId)
            ->latest('opened_at');

        if ($outletId) {
            $sessions = (clone $query)->where('outlet_id', $outletId)->take($limit)->get();
            if ($sessions->isEmpty()) {
                $sessions = (clone $query)->whereNull('outlet_id')->take($limit)->get();
            }
        } else {
            $sessions = $query->take($limit)->get();
        }

        return $sessions->map(function (CashierSession $session) use ($summaryService) {
            $summary = $summaryService->generate($session);

            $openedAt = $session->opened_at ? $session->opened_at->copy() : null;
            $closedAt = $session->closed_at ? $session->closed_at->copy() : null;
            $appTimezone = config('app.timezone', 'UTC');
            $productSales = [
                'quantity' => (int) ($summary['sales_products_total_quantity'] ?? $summary['sales_items_total_quantity'] ?? 0),
                'net' => (float) ($summary['sales_products_total_net'] ?? $summary['sales_items_total_net'] ?? 0),
            ];
            $addonSales = [
                'quantity' => (int) ($summary['sales_addons_total_quantity'] ?? 0),
                'net' => (float) ($summary['sales_addons_total_net'] ?? 0),
            ];

            return [
                'id' => $session->id,
                'status' => $session->status,
                'opened_by' => $session->openedBy?->name,
                'closed_by' => $session->closedBy?->name,
                'opened_at_iso' => $openedAt?->toIso8601String(),
                'closed_at_iso' => $closedAt?->toIso8601String(),
                'opened_at_display' => $openedAt?->setTimezone($appTimezone)->format('Y-m-d H:i:s'),
                'closed_at_display' => $closedAt?->setTimezone($appTimezone)->format('Y-m-d H:i:s'),
                'totals' => $summary['totals'],
                'transactions' => $summary['transactions'],
                'cash_balance' => $summary['cash_balance'],
                'product_sales' => $productSales,
                'addon_sales' => $addonSales,
            ];
        });
    }

    private function resolveOutletSnapshot(): array
    {
        $activeOutlet = OutletContext::currentOutlet();
        $currentRole = OutletContext::currentRole();
        $accessibleCategoryIds = [];
        $assignedCategoryNames = collect();
        $ownerUserIds = [];

        if ($activeOutlet) {
            $ownerUserIds = $activeOutlet->owners()->pluck('users.id')->unique()->values()->all();
        }

        if (empty($ownerUserIds)) {
            $ownerUserIds = [auth()->id()];
        }

        if ($currentRole && $currentRole->role === 'partner' && $activeOutlet) {
            /** @var PartnerCategoryAccessService $access */
            $access = app(PartnerCategoryAccessService::class);
            $categoryIds = $access->accessibleCategoryIdsFor(auth()->user(), $activeOutlet);
            $accessibleCategoryIds = $categoryIds === ['*'] ? ['*'] : (array) $categoryIds;

            if ($accessibleCategoryIds === ['*']) {
                $assignedCategoryNames = collect(['Semua Kategori']);
            } elseif (! empty($accessibleCategoryIds)) {
                $assignedCategoryNames = Category::whereIn('id', $accessibleCategoryIds)
                    ->orderBy('name')
                    ->pluck('name');
            }
        }

        return [
            'activeOutlet' => $activeOutlet,
            'currentOutletRole' => $currentRole,
            'accessibleCategoryIds' => $accessibleCategoryIds,
            'assignedCategoryNames' => $assignedCategoryNames,
            'ownerUserIds' => $ownerUserIds,
        ];
    }

    private function calculatePartnerMonthlyOmzet(array $context, array $ownerUserIds, ?int $outletId, int $month, int $year): ?float
    {
        $role = $context['currentOutletRole'] ?? null;

        if (! $role || $role->role !== 'partner') {
            return null;
        }

        $accessibleCategoryIds = $context['accessibleCategoryIds'] ?? [];

        if (is_array($accessibleCategoryIds) && empty($accessibleCategoryIds)) {
            return 0.0;
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->when($outletId, fn ($query) => $query->where('orders.outlet_id', $outletId))
            ->when(! $outletId, fn ($query) => $query->whereIn('orders.user_id', $ownerUserIds))
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$start, $end]);

        if ($this->shouldFilterCategories($accessibleCategoryIds)) {
            $query->whereIn('products.category_id', $accessibleCategoryIds);
        }

        return (float) $query->sum('order_items.total_price');
    }

    private function buildPartnerSummary(array $context, ?float $monthlyOmzet, int $monthlyOrders, float $monthlyAov): ?array
    {
        $role = $context['currentOutletRole'] ?? null;

        if (! $role || $role->role !== 'partner') {
            return null;
        }

        return [
            'monthly_omzet' => $monthlyOmzet ?? 0.0,
            'monthly_orders' => $monthlyOrders,
            'monthly_aov' => $monthlyAov,
        ];
    }

    private function buildPartnerChart(array $context, array $ownerUserIds, ?int $outletId): ?array
    {
        $role = $context['currentOutletRole'] ?? null;
        $accessibleCategoryIds = $context['accessibleCategoryIds'] ?? [];

        if (! $role || $role->role !== 'partner') {
            return null;
        }

        if (! $this->hasProductCategoryColumn()) {
            return null;
        }

        if ($this->shouldFilterCategories($accessibleCategoryIds) && empty($accessibleCategoryIds)) {
            return null;
        }

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $dailyRows = OrderItem::select([
                DB::raw('DATE(orders.created_at) as order_date'),
                DB::raw('SUM(order_items.total_price) as total_revenue')
            ])
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->when($outletId, fn ($query) => $query->where('orders.outlet_id', $outletId))
            ->when(! $outletId, fn ($query) => $query->whereIn('orders.user_id', $ownerUserIds))
            ->where('orders.status', 'completed')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end])
            ->when($this->shouldFilterCategories($accessibleCategoryIds), function ($q) use ($accessibleCategoryIds) {
                $q->whereIn('products.category_id', $accessibleCategoryIds);
            })
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->orderBy('order_date')
            ->get();

        if ($dailyRows->isEmpty()) {
            return null;
        }

        $dailyMap = $dailyRows->pluck('total_revenue', 'order_date');
        $period = CarbonPeriod::create($start, $end);
        $dailyLabels = [];
        $dailyValues = [];

        foreach ($period as $day) {
            $key = $day->format('Y-m-d');
            $dailyLabels[] = $day->format('d');
            $dailyValues[] = (float) ($dailyMap[$key] ?? 0);
        }

        $categoryRows = OrderItem::select([
                DB::raw('COALESCE(categories.name, "Tanpa Kategori") as category_name'),
                DB::raw('SUM(order_items.total_price) as total_revenue')
            ])
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->when($outletId, fn ($query) => $query->where('orders.outlet_id', $outletId))
            ->when(! $outletId, fn ($query) => $query->whereIn('orders.user_id', $ownerUserIds))
            ->where('orders.status', 'completed')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$start, $end])
            ->when($this->shouldFilterCategories($accessibleCategoryIds), function ($q) use ($accessibleCategoryIds) {
                $q->whereIn('products.category_id', $accessibleCategoryIds);
            })
            ->groupBy('category_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        $categoryTotals = $categoryRows->map(function ($row) {
            return [
                'label' => $row->category_name,
                'value' => (float) $row->total_revenue,
            ];
        })->values()->all();

        return [
            'daily' => [
                'labels' => $dailyLabels,
                'values' => $dailyValues,
            ],
            'categoryTotals' => [
                'data' => $categoryTotals,
            ],
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
