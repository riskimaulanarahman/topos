<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Notifications\RawMaterialStockSummary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class RawMaterialStockSummaryService
{
    public function buildSummary(float $nearPercent, ?string $timezone = null, ?string $locale = null): array
    {
        $timezoneName = $this->normalizeTimezone($timezone);
        $localeName = $this->normalizeLocale($locale);
        $generatedAt = now($timezoneName);

        $materials = RawMaterial::query()
            ->accessibleBy(Auth::user())
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'stock_qty', 'min_stock']);

        $summary = [
            'generated_at' => $generatedAt,
            'timezone' => $timezoneName,
            'locale' => $localeName,
            'near_percent_formatted' => $this->formatNumber($nearPercent, $localeName),
            'near_percent' => $nearPercent,
            'categories' => [
                'below_min' => [],
                'near_min' => [],
                'safe' => [],
            ],
        ];

        $nearFactor = $nearPercent > 0 ? (1 + ($nearPercent / 100)) : 1.0;

        foreach ($materials as $material) {
            $stock = (float) $material->stock_qty;
            $min = $material->min_stock !== null ? (float) $material->min_stock : null;
            $difference = is_null($min) ? null : ($stock - $min);

            $item = [
                'id' => $material->id,
                'sku' => $material->sku,
                'name' => $material->name,
                'stock' => $stock,
                'min' => $min,
                'stock_formatted' => $this->formatNumber($stock, $localeName),
                'min_formatted' => $min !== null ? $this->formatNumber($min, $localeName) : null,
                'difference' => $difference,
                'difference_formatted' => $difference !== null ? $this->formatNumber($difference, $localeName) : null,
            ];

            if ($stock <= 0) {
                $summary['categories']['below_min'][] = $item;
                continue;
            }

            if (is_null($min) || $min < 0) {
                $summary['categories']['safe'][] = $item;
                continue;
            }

            if ($stock <= $min) {
                $summary['categories']['below_min'][] = $item;
            } elseif ($nearPercent > 0 && $stock <= ($min * $nearFactor)) {
                $summary['categories']['near_min'][] = $item;
            } else {
                $summary['categories']['safe'][] = $item;
            }
        }

        return $summary;
    }

    public function sendSummary(?float $nearPercent, ?array $recipientEmails = null, ?string $subject = null, ?string $timezone = null, ?string $locale = null): array
    {
        $percent = is_null($nearPercent) ? 10.0 : max(0.0, (float) $nearPercent);
        $timezoneName = $this->resolveTimezone($timezone);
        $localeName = $this->resolveLocale($locale);

        $summary = $this->buildSummary($percent, $timezoneName, $localeName);
        $sentTo = [];

        if ($recipientEmails && count($recipientEmails) > 0) {
            $emails = collect($recipientEmails)
                ->filter()
                ->map(fn ($email) => trim((string) $email))
                ->filter()
                ->unique()
                ->values();

            foreach ($emails as $email) {
                Notification::route('mail', $email)
                    ->notify(new RawMaterialStockSummary($summary, $subject));
                $sentTo[] = $email;
            }
        } else {
            $user = Auth::user();
            if (!$user) {
                throw ValidationException::withMessages([
                    'recipient_emails' => 'Pengguna tidak terotentikasi.',
                ]);
            }

            $email = $user->email;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'recipient_emails' => 'Email pengguna tidak tersedia atau tidak valid.',
                ]);
            }

            $user->notify(new RawMaterialStockSummary($summary, $subject));
            $sentTo[] = $email;
        }

        return [
            'summary' => $summary,
            'sent_to' => $sentTo,
        ];
    }

    private function resolveTimezone(?string $timezone): string
    {
        if ($timezone) {
            return $this->normalizeTimezone($timezone);
        }

        $userTz = Auth::user()?->timezone;
        if ($userTz) {
            return $this->normalizeTimezone($userTz);
        }

        return $this->normalizeTimezone(config('app.timezone'));
    }

    private function resolveLocale(?string $locale): string
    {
        if ($locale) {
            return $this->normalizeLocale($locale);
        }

        $userLocale = Auth::user()?->locale;
        if ($userLocale) {
            return $this->normalizeLocale($userLocale);
        }

        return $this->normalizeLocale(config('app.locale'));
    }

    private function normalizeTimezone(?string $timezone): string
    {
        try {
            return (new \DateTimeZone($timezone ?: config('app.timezone')))->getName();
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'timezone' => 'Zona waktu tidak valid.',
            ]);
        }
    }

    private function normalizeLocale(?string $locale): string
    {
        $normalized = strtolower(str_replace('-', '_', $locale ?? ''));
        if (empty($normalized)) {
            return 'id';
        }

        $supported = ['id', 'id_id', 'in', 'in_id', 'en', 'en_us', 'en_id'];
        if (!in_array($normalized, $supported)) {
            return 'id';
        }

        if (Str::startsWith($normalized, 'en')) {
            return 'en';
        }

        return 'id';
    }

    private function formatNumber(float $value, string $locale): string
    {
        $decimalSeparator = $locale === 'en' ? '.' : ',';
        $thousandSeparator = $locale === 'en' ? ',' : '.';

        $formatted = number_format($value, 4, $decimalSeparator, $thousandSeparator);
        $pattern = sprintf('/%s?0+$/', preg_quote($decimalSeparator, '/'));
        $formatted = preg_replace($pattern, '', $formatted);

        if (str_ends_with($formatted, $decimalSeparator)) {
            $formatted = substr($formatted, 0, -1);
        }

        if ($formatted === '' || $formatted === '-' ) {
            return '0';
        }

        return $formatted;
    }
}
