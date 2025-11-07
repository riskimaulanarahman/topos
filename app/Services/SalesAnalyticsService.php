<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsService
{
    /**
     * Build revenue timeseries.
     *
     * @param array<int,int> $ownerUserIds
     * @param int|null $outletId
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @param string $bucket day|week|month|year
     * @param string|null $segmentBy payment_method|status|method_status|null
     * @return array{labels: array<int,string>, datasets: array<int, array{label: string, data: array<int,int>}>}
     */
    public function timeseries(array $ownerUserIds, ?int $outletId, string $from, string $to, string $bucket = 'day', ?string $segmentBy = null, ?string $status = null): array
    {
        $ownerUserIds = array_values(array_unique(array_map('intval', $ownerUserIds)));
        sort($ownerUserIds);

        if (empty($ownerUserIds)) {
            return ['labels' => [], 'datasets' => []];
        }

        [$selectExpr, $groupExpr, $labelFormatter] = $this->bucketExpr($bucket);

        // Build segment select supporting combined method+status when requested
        $segmentSelect = "'All'";
        if ($segmentBy === 'method_status') {
            $segmentSelect = "CONCAT(COALESCE(payment_method,'Unknown'),' - ',COALESCE(status,'Unknown'))";
        } elseif (!empty($segmentBy)) {
            // safe because only specific allowed values are used by controllers/views
            $segmentSelect = $segmentBy;
        }

        $base = DB::table('orders')
            ->selectRaw("$selectExpr as bucket")
            ->selectRaw("$segmentSelect as segment")
            ->selectRaw('SUM(total_price) as revenue')
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId))
            ->when(! $outletId, fn ($query) => $query->whereIn('user_id', $ownerUserIds))
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->when($status, fn($q) => $q->where('status', $status))
            ->groupByRaw($segmentBy ? ($segmentBy === 'method_status' ? "$groupExpr, payment_method, status" : "$groupExpr, $segmentBy") : $groupExpr)
            ->orderBy('bucket');

        $rows = $base->get();

        // Unique raw buckets sorted
        $rawBuckets = $rows->pluck('bucket')->unique()->sort()->values()->all();
        $labels = array_map($labelFormatter, $rawBuckets);

        $segments = $rows->pluck('segment')->unique()->values()->all();
        if (empty($segments)) $segments = ['All'];

        // Zero-fill
        $datasetMap = [];
        foreach ($segments as $seg) {
            $datasetMap[$seg] = array_fill(0, count($labels), 0);
        }

        // Map raw bucket -> index
        $bucketIndex = array_flip($rawBuckets);

        foreach ($rows as $r) {
            $idx = $bucketIndex[$r->bucket] ?? null;
            if ($idx !== null) {
                $seg = $r->segment ?? 'All';
                $datasetMap[$seg][$idx] = (int) $r->revenue;
            }
        }

        $datasets = [];
        foreach ($datasetMap as $seg => $data) {
            $datasets[] = [
                'label' => $seg,
                'data' => $data,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * Return [selectExpr, groupExpr, labelFormatter]
     */
    private function bucketExpr(string $bucket): array
    {
        switch ($bucket) {
            case 'week':
                return [
                    'YEARWEEK(created_at, 3)',
                    'YEARWEEK(created_at, 3)',
                    fn($b) => $this->formatYearWeek((string) $b),
                ];
            case 'month':
                return [
                    "DATE_FORMAT(created_at, '%Y-%m')",
                    "DATE_FORMAT(created_at, '%Y-%m')",
                    fn($b) => (string) $b,
                ];
            case 'year':
                return [
                    'YEAR(created_at)',
                    'YEAR(created_at)',
                    fn($b) => (string) $b,
                ];
            case 'day':
            default:
                return [
                    'DATE(created_at)',
                    'DATE(created_at)',
                    fn($b) => Carbon::parse($b)->format('Y-m-d'),
                ];
        }
    }

    private function formatYearWeek(string $yearWeek): string
    {
        // e.g., 202536 -> 2025 W36
        $year = substr($yearWeek, 0, 4);
        $week = substr($yearWeek, -2);
        return $year . ' W' . $week;
    }
}
