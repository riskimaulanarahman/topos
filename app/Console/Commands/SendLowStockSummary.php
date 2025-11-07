<?php

namespace App\Console\Commands;

use App\Models\RawMaterial;
use App\Models\User;
use App\Notifications\LowStockSummary;
use Illuminate\Console\Command;

class SendLowStockSummary extends Command
{
    protected $signature = 'inventory:send-low-stock-summary {--force : Send even if empty (for testing)}';
    protected $description = 'Send daily low-stock summary email to administrators';

    public function handle(): int
    {
        $items = RawMaterial::query()
            ->whereColumn('stock_qty', '<=', 'min_stock')
            ->orderBy('name')
            ->get(['sku','name','stock_qty','min_stock'])
            ->map(fn($m) => [
                'sku' => $m->sku,
                'name' => $m->name,
                'stock' => (float)$m->stock_qty,
                'min' => (float)$m->min_stock,
            ])->values()->all();

        if (empty($items) && !$this->option('force')) {
            $this->info('No low-stock items. Skipping email.');
            return self::SUCCESS;
        }

        $admins = User::where('roles', 'admin')->get();
        if ($admins->isEmpty()) {
            $this->warn('No admin users found.');
            return self::SUCCESS;
        }

        foreach ($admins as $admin) {
            $admin->notify(new LowStockSummary($items));
        }

        $this->info('Low-stock summary sent to '.count($admins).' admin(s).');
        return self::SUCCESS;
    }
}

