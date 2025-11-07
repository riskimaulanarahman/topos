<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark users whose trials or subscriptions have lapsed as expired';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();

        $updated = User::query()
            ->whereIn('subscription_status', [
                SubscriptionStatus::TRIALING->value,
                SubscriptionStatus::ACTIVE->value,
            ])
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<', $now)
            ->update([
                'subscription_status' => SubscriptionStatus::EXPIRED->value,
                'updated_at' => $now,
            ]);

        $this->info("Marked {$updated} user(s) as expired.");

        return self::SUCCESS;
    }
}
