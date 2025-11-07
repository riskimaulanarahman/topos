<?php

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('trial_started_at')->nullable()->after('remember_token');
            $table->timestamp('subscription_expires_at')->nullable()->after('trial_started_at');
            $table->string('subscription_status')->default(SubscriptionStatus::TRIALING->value)->after('subscription_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'trial_started_at',
                'subscription_expires_at',
                'subscription_status',
            ]);
        });
    }
};
