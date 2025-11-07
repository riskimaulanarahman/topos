<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plan_code')->nullable();
            $table->string('plan_name')->nullable();
            $table->string('plan_duration')->nullable();
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('unique_code')->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('payment_channel')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->string('payer_name')->nullable();
            $table->text('customer_note')->nullable();
            $table->json('destination_snapshot')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['status', 'plan_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_submissions');
    }
};
