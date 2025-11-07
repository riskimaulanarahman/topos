<?php

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
        Schema::create('cashier_outflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_id')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->string('category')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_offline')->default(false);
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['cashier_session_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_outflows');
    }
};
