<?php

namespace App\Notifications;

use App\Models\CashierClosureReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class CashierSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CashierClosureReport $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $summary = $this->report->summary ?? [];
        $session = $summary['session'] ?? [];
        $outlet = $summary['outlet'] ?? [];
        $totals = $summary['totals'] ?? [];
        $payments = $summary['payments'] ?? [];
        $transactions = $summary['transactions'] ?? [];
        $cashBalance = $summary['cash_balance'] ?? [];
        $outflows = $summary['outflows'] ?? [];
        $salesItems = collect($summary['sales_items'] ?? []);
        $productItems = $salesItems->filter(fn ($item) => empty($item['is_addon']))->values()->all();
        $addonItems = $salesItems->filter(fn ($item) => !empty($item['is_addon']))->values()->all();
        $salesItemsTotalQuantity = $summary['sales_items_total_quantity']
            ?? collect($productItems)->sum(fn ($item) => (int) ($item['quantity'] ?? 0));
        $salesItemsTotalNet = $summary['sales_items_total_net']
            ?? collect($productItems)->sum(fn ($item) => (float) ($item['net_total'] ?? 0));
        $addonItemsTotalQuantity = $summary['sales_addons_total_quantity']
            ?? collect($addonItems)->sum(fn ($item) => (int) ($item['quantity'] ?? 0));
        $addonItemsTotalNet = $summary['sales_addons_total_net']
            ?? collect($addonItems)->sum(fn ($item) => (float) ($item['net_total'] ?? 0));
        $date = Carbon::now()->translatedFormat('d F Y');
        $timezoneOffset = isset($session['timezone_offset']) ? (int) $session['timezone_offset'] : null;
        $sessionTimezone = $session['timezone']
            ?? optional($this->report->session?->opened_at)->timezoneName
            ?? optional($this->report->session?->closed_at)->timezoneName;

        if (!$sessionTimezone && $timezoneOffset === null) {
            $sessionTimezone = config('app.timezone');
        }

        return (new MailMessage)
            ->subject('Ringkasan Tutup Kasir - '.($this->report->user->store_name ?? config('app.name')).' ('.$date.')')
            ->markdown('emails.cashier_summary', [
                'appName' => config('app.name'),
                'logoUrl' => asset('img/toga-gold-ts.png'),
                'report' => $this->report,
                'session' => $session,
                'outlet' => $outlet,
                'totals' => $totals,
                'payments' => $payments,
                'transactions' => $transactions,
                'cashBalance' => $cashBalance,
                'outflows' => $outflows,
                'sessionTimezone' => $sessionTimezone,
                'sessionTimezoneOffset' => $timezoneOffset,
                'productSalesItems' => $productItems,
                'addonSalesItems' => $addonItems,
                'salesItemsTotalQuantity' => $salesItemsTotalQuantity,
                'salesItemsTotalNet' => $salesItemsTotalNet,
                'addonItemsTotalQuantity' => $addonItemsTotalQuantity,
                'addonItemsTotalNet' => $addonItemsTotalNet,
            ]);
    }
}
