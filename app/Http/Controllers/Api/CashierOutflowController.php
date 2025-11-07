<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierOutflow;
use App\Models\CashierSession;
use App\Support\OutletContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashierOutflowController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $session = $this->resolveSessionForRequest($user->id, $request->integer('session_id'), allowClosed: true);
        if (!$session) {
            return response()->json([
                'message' => 'Cashier session tidak ditemukan',
                'data' => [],
            ]);
        }

        $since = $request->input('since') ? Carbon::parse($request->input('since')) : null;

        $outflows = CashierOutflow::query()
            ->where('cashier_session_id', $session->id)
            ->where('user_id', $user->id)
            ->when($since, fn ($query) => $query->where('updated_at', '>', $since))
            ->orderBy('recorded_at')
            ->get();

        return response()->json([
            'message' => 'Cashier outflows retrieved',
            'data' => [
                'session_id' => $session->id,
                'outflows' => $outflows,
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $outletId = OutletContext::currentOutlet()?->id;
        if (!$outletId && $request->filled('outlet_id')) {
            $outletId = (int) $request->input('outlet_id');
        }

        $limit = (int) $request->input('limit', 30);
        if ($limit < 1) {
            $limit = 1;
        } elseif ($limit > 100) {
            $limit = 100;
        }

        $sessions = CashierSession::query()
            ->where('user_id', $user->id)
            ->when($outletId, function ($query) use ($outletId) {
                $query->where(function ($q) use ($outletId) {
                    $q->where('outlet_id', $outletId)
                        ->orWhereNull('outlet_id');
                });
            })
            ->orderByDesc('opened_at')
            ->limit($limit)
            ->with(['outflows' => function ($query) {
                $query->orderByDesc('recorded_at');
            }])
            ->get();

        $history = $sessions->map(function (CashierSession $session) {
            return [
                'session' => [
                    'id' => $session->id,
                    'opened_at' => $session->opened_at?->toIso8601String(),
                    'closed_at' => $session->closed_at?->toIso8601String(),
                    'status' => $session->status,
                    'remarks' => $session->remarks,
                    'opening_balance' => (float) ($session->opening_balance ?? 0),
                    'closing_balance' => $session->closing_balance !== null
                        ? (float) $session->closing_balance
                        : null,
                ],
                'outflows' => $session->outflows->map(function (CashierOutflow $outflow) {
                    return [
                        'id' => $outflow->id,
                        'cashier_session_id' => $outflow->cashier_session_id,
                        'amount' => (float) $outflow->amount,
                        'category' => $outflow->category,
                        'note' => $outflow->note,
                        'is_offline' => (bool) $outflow->is_offline,
                        'recorded_at' => $outflow->recorded_at?->toIso8601String(),
                        'synced_at' => $outflow->synced_at?->toIso8601String(),
                        'created_at' => $outflow->created_at?->toIso8601String(),
                        'updated_at' => $outflow->updated_at?->toIso8601String(),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'message' => 'Cashier outflow history retrieved',
            'data' => $history,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'category' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
            'recorded_at' => ['nullable', 'date'],
            'session_id' => ['nullable', 'integer'],
            'client_id' => ['nullable', 'string', 'max:100'],
            'is_offline' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $session = $this->resolveSessionForRequest($user->id, $payload['session_id'] ?? null);
        if (!$session) {
            return response()->json([
                'message' => 'Tidak ada sesi kasir aktif',
            ], 409);
        }

        $clientId = $payload['client_id'] ?? (string) Str::uuid();
        $recordedAt = isset($payload['recorded_at'])
            ? Carbon::parse($payload['recorded_at'])
            : now();

        $outflow = CashierOutflow::query()
            ->where('user_id', $user->id)
            ->where('client_id', $clientId)
            ->first();

        $normalizedCategory = $this->normalizeCategory($payload['category'] ?? null);

        if ($outflow) {
            $outflow->forceFill([
                'cashier_session_id' => $session->id,
                'outlet_id' => $session->outlet_id,
                'amount' => round($payload['amount'], 2),
                'category' => $normalizedCategory ?? $outflow->category,
                'note' => $payload['note'] ?? $outflow->note,
                'recorded_at' => $recordedAt,
                'is_offline' => (bool) ($payload['is_offline'] ?? $outflow->is_offline),
                'synced_at' => now(),
            ])->save();
        } else {
            $outflow = CashierOutflow::create([
                'cashier_session_id' => $session->id,
                'user_id' => $user->id,
                'outlet_id' => $session->outlet_id,
                'client_id' => $clientId,
                'amount' => round($payload['amount'], 2),
                'category' => $normalizedCategory,
                'note' => $payload['note'] ?? null,
                'recorded_at' => $recordedAt,
                'is_offline' => (bool) ($payload['is_offline'] ?? false),
                'synced_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Cashier outflow recorded',
            'data' => $outflow->fresh(),
        ], 201);
    }

    public function sync(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'outflows' => ['required', 'array', 'min:1'],
            'outflows.*.client_id' => ['required', 'string', 'max:100'],
            'outflows.*.session_id' => ['required', 'integer'],
            'outflows.*.amount' => ['required', 'numeric', 'gt:0'],
            'outflows.*.category' => ['nullable', 'string', 'max:100'],
            'outflows.*.note' => ['nullable', 'string'],
            'outflows.*.recorded_at' => ['nullable', 'date'],
            'outflows.*.is_offline' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $results = [];

        DB::transaction(function () use ($payload, $user, &$results) {
            foreach ($payload['outflows'] as $item) {
                $session = $this->resolveSessionForRequest($user->id, (int) $item['session_id'], allowClosed: true);
                if (!$session) {
                    $results[] = [
                        'client_id' => $item['client_id'],
                        'status' => 'failed',
                        'message' => 'Cashier session tidak ditemukan',
                    ];
                    continue;
                }

                $outflow = CashierOutflow::query()
                    ->where('user_id', $user->id)
                    ->where('client_id', $item['client_id'])
                    ->first();

                $recordedAt = isset($item['recorded_at'])
                    ? Carbon::parse($item['recorded_at'])
                    : now();

                if ($outflow) {
                    $outflow->forceFill([
                        'cashier_session_id' => $session->id,
                        'outlet_id' => $session->outlet_id,
                        'amount' => round($item['amount'], 2),
                        'category' => $this->normalizeCategory($item['category'] ?? null) ?? $outflow->category,
                        'note' => $item['note'] ?? $outflow->note,
                        'recorded_at' => $recordedAt,
                        'is_offline' => (bool) ($item['is_offline'] ?? $outflow->is_offline),
                        'synced_at' => now(),
                    ])->save();
                } else {
                    $outflow = CashierOutflow::create([
                        'cashier_session_id' => $session->id,
                        'user_id' => $user->id,
                        'outlet_id' => $session->outlet_id,
                        'client_id' => $item['client_id'],
                        'amount' => round($item['amount'], 2),
                        'category' => $this->normalizeCategory($item['category'] ?? null),
                        'note' => $item['note'] ?? null,
                        'recorded_at' => $recordedAt,
                        'is_offline' => (bool) ($item['is_offline'] ?? true),
                        'synced_at' => now(),
                    ]);
                }

                $results[] = [
                    'client_id' => $item['client_id'],
                    'status' => 'synced',
                    'id' => $outflow->id,
                    'session_id' => $session->id,
                ];
            }
        });

        return response()->json([
            'message' => 'Cashier outflows synchronised',
            'data' => $results,
        ]);
    }

    private function resolveSessionForRequest(int $userId, ?int $sessionId, bool $allowClosed = false): ?CashierSession
    {
        $outletId = OutletContext::currentOutlet()?->id;

        if ($sessionId) {
            return CashierSession::query()
                ->where('id', $sessionId)
                ->where('user_id', $userId)
                ->when($outletId, function ($query) use ($outletId) {
                    $query->where(function ($q) use ($outletId) {
                        $q->where('outlet_id', $outletId)
                            ->orWhereNull('outlet_id');
                    });
                })
                ->when(!$allowClosed, fn ($query) => $query->where('status', 'open'))
                ->first();
        }

        return CashierSession::query()
            ->where('user_id', $userId)
            ->when($outletId, function ($query) use ($outletId) {
                $query->where(function ($q) use ($outletId) {
                    $q->where('outlet_id', $outletId)
                        ->orWhereNull('outlet_id');
                });
            })
            ->when(!$allowClosed, fn ($query) => $query->where('status', 'open'))
            ->latest('opened_at')
            ->first();
    }

    private function normalizeCategory(?string $category): ?string
    {
        if ($category === null) {
            return null;
        }

        $normalized = trim($category);
        return $normalized === '' ? null : mb_strtolower($normalized);
    }
}
