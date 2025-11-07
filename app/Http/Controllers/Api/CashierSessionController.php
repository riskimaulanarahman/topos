<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierClosureReport;
use App\Models\CashierSession;
use App\Models\RawMaterial;
use App\Notifications\LowStockSummary;
use App\Services\CashierSummaryService;
use App\Support\OutletContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Throwable;

class CashierSessionController extends Controller
{
    public function __construct(private CashierSummaryService $summaryService)
    {
    }
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user?->id;
        $outletId = OutletContext::currentOutlet()?->id;
        if (! $outletId && $request->filled('outlet_id')) {
            $outletId = (int) $request->input('outlet_id');
        }

        $activeSession = null;
        if ($userId) {
            $base = CashierSession::where('user_id', $userId)->where('status', 'open')->latest('opened_at');
            if ($outletId) {
                $activeSession = (clone $base)->where('outlet_id', $outletId)->first();
                if (! $activeSession) {
                    $activeSession = (clone $base)->whereNull('outlet_id')->first();
                }
            } else {
                $activeSession = $base->first();
            }
        }

        return response()->json([
            'message' => 'Cashier status retrieved',
            'data' => [
                'status' => $activeSession ? 'open' : 'closed',
                'session' => $activeSession,
            ],
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated',
            ], 401);
        }

        $outletId = OutletContext::currentOutlet()?->id;
        if (! $outletId && $request->filled('outlet_id')) {
            $outletId = (int) $request->input('outlet_id');
        }

        $reportQuery = CashierClosureReport::with(['session' => function ($query) use ($outletId) {
            $query->select([
                'id',
                'user_id',
                'outlet_id',
                'opening_balance',
                'closing_balance',
                'opened_at',
                'closed_at',
                'remarks',
                'status',
            ])->when($outletId, fn ($q) => $q->where('outlet_id', $outletId));
        }])
            ->where('user_id', $user->id)
            ->when($outletId, fn ($query) => $query->whereHas('session', fn ($q) => $q->where('outlet_id', $outletId)))
            ->latest('created_at');

        $reports = $reportQuery->get();

        if ($reports->isEmpty() && $outletId) {
            $reports = CashierClosureReport::with(['session' => function ($query) {
                $query->select([
                    'id',
                    'user_id',
                    'outlet_id',
                    'opening_balance',
                    'closing_balance',
                    'opened_at',
                    'closed_at',
                    'remarks',
                    'status',
                ])->whereNull('outlet_id');
            }])
                ->where('user_id', $user->id)
                ->whereHas('session', fn ($q) => $q->whereNull('outlet_id'))
                ->latest('created_at')
                ->get();
        }

        return response()->json([
            'message' => 'Cashier closure reports retrieved',
            'data' => $reports,
        ]);
    }

    public function open(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'opening_balance' => ['required', 'numeric', 'gte:0'],
            'remarks' => ['nullable', 'string'],
            'outlet_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $userId = $user?->id;

        if (!$userId) {
            return response()->json([
                'message' => 'User not authenticated',
            ], 401);
        }

        $outletId = OutletContext::currentOutlet()?->id;
        if (! $outletId && isset($payload['outlet_id'])) {
            $outletId = (int) $payload['outlet_id'];
        }

        if (! $outletId) {
            return response()->json([
                'message' => 'Outlet aktif tidak ditemukan',
            ], 400);
        }

        $existing = CashierSession::where('user_id', $userId)
            ->where(function ($query) use ($outletId) {
                $query->where('outlet_id', $outletId)
                    ->orWhereNull('outlet_id');
            })
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Cashier session already open',
                'data' => $existing,
            ], 409);
        }

        try {
            $session = DB::transaction(function () use ($payload, $userId, $user, $outletId) {
                return CashierSession::create([
                    'user_id' => $userId,
                    'outlet_id' => $outletId,
                    'opening_balance' => round($payload['opening_balance'], 2),
                    'opened_at' => now(),
                    'opened_by' => $user?->id,
                    'remarks' => $payload['remarks'] ?? null,
                    'status' => 'open',
                ]);
            });
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Failed to open cashier session',
            ], 500);
        }

        return response()->json([
            'message' => 'Cashier session opened',
            'data' => $session,
        ], 201);
    }

    public function close(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'closing_balance' => ['required', 'numeric', 'gte:0'],
            'remarks' => ['nullable', 'string'],
            'timezone' => ['nullable', 'timezone'],
            'timezone_offset' => ['nullable', 'integer'],
            'outlet_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $userId = $user?->id;

        if (!$userId) {
            return response()->json([
                'message' => 'User not authenticated',
            ], 401);
        }

        $outletId = OutletContext::currentOutlet()?->id;

        $outletId = OutletContext::currentOutlet()?->id;
        if (! $outletId && isset($payload['outlet_id'])) {
            $outletId = (int) $payload['outlet_id'];
        }

        if (! $outletId) {
            return response()->json([
                'message' => 'Outlet aktif tidak ditemukan',
            ], 400);
        }

        $session = CashierSession::where('user_id', $userId)
            ->when($outletId, fn ($query) => $query->where(function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId)
                    ->orWhereNull('outlet_id');
            }))
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'No active cashier session',
            ], 409);
        }

        $timezone = $payload['timezone'] ?? null;
        $timezoneOffset = $payload['timezone_offset'] ?? null;

        try {
            [$session, $summary, $report] = DB::transaction(function () use ($session, $payload, $user, $timezone, $timezoneOffset) {
                $session->update([
                    'closing_balance' => round($payload['closing_balance'], 2),
                    'closed_at' => now(),
                    'closed_by' => $user?->id,
                    'remarks' => $payload['remarks'] ?? $session->remarks,
                    'status' => 'closed',
                ]);

                $session->refresh();
                if ($timezone) {
                    $session->setAttribute('timezone', $timezone);
                }
                if ($timezoneOffset !== null) {
                    $session->setAttribute('timezone_offset', (int) $timezoneOffset);
                }

                $summary = $this->summaryService->generate($session, $timezone, $timezoneOffset);
                $report = $this->summaryService->createReport(
                    $session,
                    $summary,
                    $user?->email
                );

                return [$session, $summary, $report];
            });
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Failed to close cashier session',
            ], 500);
        }

        $this->summaryService->dispatchEmail($report);
        $this->sendLowStockAlert($user);

        $sessionResponse = $session->fresh();
        $sessionResponse->setAttribute('timezone', $summary['session']['timezone'] ?? null);
        $sessionResponse->setAttribute('timezone_offset', $summary['session']['timezone_offset'] ?? null);

        return response()->json([
            'message' => 'Cashier session closed',
            'data' => [
                'session' => $sessionResponse,
                'summary' => $summary,
                'report_id' => $report->id,
            ],
        ]);
    }

    public function resendEmail(Request $request, CashierClosureReport $report): JsonResponse
    {
        $user = $request->user();

        if (!$user || $report->user_id !== $user->id) {
            return response()->json([
                'message' => 'Laporan tidak ditemukan',
            ], 404);
        }

        $payload = $request->validate([
            'email' => ['nullable', 'email'],
            'sales_items' => ['nullable', 'array'],
            'sales_items.*.name' => ['required_with:sales_items', 'string', 'max:255'],
            'sales_items.*.variant' => ['nullable', 'string', 'max:255'],
            'sales_items.*.sku' => ['nullable', 'string', 'max:255'],
            'sales_items.*.product_id' => ['nullable', 'integer'],
            'sales_items.*.quantity' => ['required_with:sales_items', 'integer', 'min:0'],
            'sales_items.*.gross_total' => ['nullable', 'numeric'],
            'sales_items.*.discount_total' => ['nullable', 'numeric'],
            'sales_items.*.net_total' => ['nullable', 'numeric'],
            'sales_items.*.is_addon' => ['nullable', 'boolean'],
            'sales_items_total_quantity' => ['nullable', 'integer', 'min:0'],
            'sales_items_total_net' => ['nullable', 'numeric'],
            'product_sales' => ['nullable', 'array'],
            'product_sales.*.name' => ['required_with:product_sales', 'string', 'max:255'],
            'product_sales.*.variant' => ['nullable', 'string', 'max:255'],
            'product_sales.*.sku' => ['nullable', 'string', 'max:255'],
            'product_sales.*.product_id' => ['nullable', 'integer'],
            'product_sales.*.quantity' => ['required_with:product_sales', 'integer', 'min:0'],
            'product_sales.*.gross_total' => ['nullable', 'numeric'],
            'product_sales.*.discount_total' => ['nullable', 'numeric'],
            'product_sales.*.net_total' => ['nullable', 'numeric'],
            'product_sales.*.is_addon' => ['nullable', 'boolean'],
            'product_sales_total_quantity' => ['nullable', 'integer', 'min:0'],
            'product_sales_total_amount' => ['nullable', 'numeric'],
            'sales_addons_total_quantity' => ['nullable', 'integer', 'min:0'],
            'sales_addons_total_net' => ['nullable', 'numeric'],
            'addon_items' => ['nullable', 'array'],
            'addon_items.*.name' => ['required_with:addon_items', 'string', 'max:255'],
            'addon_items.*.variant' => ['nullable', 'string', 'max:255'],
            'addon_items.*.sku' => ['nullable', 'string', 'max:255'],
            'addon_items.*.product_id' => ['nullable', 'integer'],
            'addon_items.*.quantity' => ['required_with:addon_items', 'integer', 'min:0'],
            'addon_items.*.gross_total' => ['nullable', 'numeric'],
            'addon_items.*.discount_total' => ['nullable', 'numeric'],
            'addon_items.*.net_total' => ['nullable', 'numeric'],
            'addon_items.*.is_addon' => ['nullable', 'boolean'],
        ]);

        $email = $payload['email'] ?? $report->email_to;

        if (!$email) {
            return response()->json([
                'message' => 'Alamat email tidak tersedia untuk laporan ini',
            ], 422);
        }

        $summary = $report->summary ?? [];
        $salesItemsInput = $payload['sales_items'] ?? null;
        if ($salesItemsInput === null && isset($payload['product_sales'])) {
            $salesItemsInput = $payload['product_sales'];
        }
        if (!empty($payload['addon_items'])) {
            $salesItemsInput = array_merge(
                is_array($salesItemsInput) ? $salesItemsInput : [],
                $payload['addon_items']
            );
        }

        if ($salesItemsInput !== null) {
            $normalizedSales = collect($salesItemsInput)->map(function ($item) {
                $quantity = (int) Arr::get($item, 'quantity', 0);
                $gross = (float) Arr::get($item, 'gross_total', 0);
                $net = (float) Arr::get($item, 'net_total', 0);
                $discount = (float) Arr::get($item, 'discount_total', $gross > $net ? $gross - $net : 0);

                return [
                    'product_id' => Arr::get($item, 'product_id'),
                    'name' => Arr::get($item, 'name', 'Produk Tidak Dikenal'),
                    'sku' => Arr::get($item, 'sku'),
                    'variant' => Arr::get($item, 'variant'),
                    'quantity' => $quantity,
                    'gross_total' => $gross,
                    'discount_total' => $discount,
                    'net_total' => $net,
                    'is_addon' => (bool) Arr::get($item, 'is_addon', false),
                ];
            })->values()->all();

            $summary['sales_items'] = $normalizedSales;
            $summary['sales_items_total_quantity'] = $payload['sales_items_total_quantity']
                ?? $payload['product_sales_total_quantity']
                ?? collect($normalizedSales)->sum(fn ($item) => (int) ($item['quantity'] ?? 0));
            $summary['sales_items_total_net'] = $payload['sales_items_total_net']
                ?? $payload['product_sales_total_amount']
                ?? collect($normalizedSales)->sum(fn ($item) => (float) ($item['net_total'] ?? 0));
            $summary['sales_addons_total_quantity'] = $payload['sales_addons_total_quantity']
                ?? collect($normalizedSales)
                    ->filter(fn ($item) => $item['is_addon'] ?? false)
                    ->sum(fn ($item) => (int) ($item['quantity'] ?? 0));
            $summary['sales_addons_total_net'] = $payload['sales_addons_total_net']
                ?? collect($normalizedSales)
                    ->filter(fn ($item) => $item['is_addon'] ?? false)
                    ->sum(fn ($item) => (float) ($item['net_total'] ?? 0));
        }

        $report->forceFill([
            'email_to' => $email,
            'email_status' => 'pending',
            'emailed_at' => null,
            'email_error' => null,
            'summary' => $summary,
        ])->save();

        $this->summaryService->dispatchEmail($report);

        return response()->json([
            'message' => 'Laporan akan dikirim ke email',
            'data' => [
                'report_id' => $report->id,
                'email_to' => $email,
            ],
        ]);
    }

    private function sendLowStockAlert(?\App\Models\User $user): void
    {
        if (!$user || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $items = RawMaterial::query()
                ->accessibleBy($user)
                ->whereNotNull('min_stock')
                ->whereColumn('stock_qty', '<=', 'min_stock')
                ->orderBy('name')
                ->get(['sku', 'name', 'stock_qty', 'min_stock'])
                ->map(fn ($material) => [
                    'sku' => $material->sku,
                    'name' => $material->name,
                    'stock' => (float) $material->stock_qty,
                    'min' => (float) $material->min_stock,
                ])
                ->values()
                ->all();

            if (empty($items)) {
                return;
            }

            $user->notify(new LowStockSummary($items));
        } catch (Throwable $e) {
            Log::warning('Failed sending low stock summary on cashier close', [
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
