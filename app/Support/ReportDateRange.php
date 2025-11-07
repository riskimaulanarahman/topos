<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportDateRange
{
    /**
     * Compute date range (Y-m-d) from request inputs.
     * Supports:
     * - period: harian|mingguan|bulanan|tahunan|custom(null)
     * - year, month
     * - week_in_month: w1..w5 OR last_days: 7|14|21|28
     * - date_from, date_to (fallback / explicit override)
     *
     * Returns [from, to, meta]: from/to are Y-m-d strings.
     */
    public static function fromRequest(Request $request): array
    {
        $period = $request->input('period');
        $year = (int)($request->input('year') ?: now()->year);
        $month = (int)($request->input('month') ?: now()->month);
        $weekInMonth = $request->input('week_in_month'); // e.g., w1..w5
        $lastDays = (int)$request->input('last_days'); // 7|14|21|28

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // If explicit date range provided, normalize and return
        if ($dateFrom && $dateTo) {
            try {
                $from = Carbon::parse($dateFrom)->toDateString();
                $to = Carbon::parse($dateTo)->toDateString();
                return [
                    'from' => $from,
                    'to' => $to,
                    'meta' => compact('period', 'year', 'month', 'weekInMonth', 'lastDays')
                ];
            } catch (\Throwable $e) {
                // fallthrough to compute below
            }
        }

        // Compute by period
        $from = null; $to = null;
        switch ($period) {
            case 'harian':
                // If month/year provided but no specific day range, default to full selected month
                [$from, $to] = self::monthRange($year, $month);
                break;
            case 'mingguan':
                if (in_array($lastDays, [7, 14, 21, 28], true)) {
                    $to = now()->toDateString();
                    $from = now()->copy()->subDays($lastDays - 1)->toDateString();
                } elseif (is_string($weekInMonth) && preg_match('/^w([1-5])$/', $weekInMonth, $m)) {
                    $weekIndex = (int)$m[1];
                    [$from, $to] = self::weekOfMonthRange($year, $month, $weekIndex);
                } else {
                    // Default: current week (Mon-Sun)
                    $from = now()->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                    $to = now()->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
                }
                break;
            case 'bulanan':
                [$from, $to] = self::monthRange($year, $month);
                break;
            case 'tahunan':
                $from = Carbon::create($year, 1, 1)->toDateString();
                $to = Carbon::create($year, 12, 31)->toDateString();
                break;
            default:
                // Custom/unknown: return as-is (nulls) to allow controllers to handle validation
                break;
        }

        return [
            'from' => $from,
            'to' => $to,
            'meta' => compact('period', 'year', 'month', 'weekInMonth', 'lastDays')
        ];
    }

    /**
     * Get first and last day of a month
     */
    public static function monthRange(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        return [$start, $end];
    }

    /**
     * Compute range for week N (1..5) in a given month.
     * Week starts Monday, spans 7 days. Week 1 begins on the first Monday of the month.
     * If month starts after Monday, week 1 starts at the first Monday.
     */
    public static function weekOfMonthRange(int $year, int $month, int $weekIndex): array
    {
        $firstOfMonth = Carbon::create($year, $month, 1);
        // Find first Monday of the month
        $firstMonday = $firstOfMonth->copy()->nextOrSame(Carbon::MONDAY);
        $start = $firstMonday->copy()->addWeeks(max(0, $weekIndex - 1));
        $end = $start->copy()->addDays(6);
        // Clamp to month boundaries
        $monthStart = $firstOfMonth->copy();
        $monthEnd = $firstOfMonth->copy()->endOfMonth();
        if ($start->lt($monthStart)) { $start = $monthStart; }
        if ($end->gt($monthEnd)) { $end = $monthEnd; }
        return [$start->toDateString(), $end->toDateString()];
    }
}

