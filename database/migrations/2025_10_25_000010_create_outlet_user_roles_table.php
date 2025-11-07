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
        if (! Schema::hasTable('outlet_user_roles')) {
            Schema::create('outlet_user_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role'); // owner, partner, staff, dll.
                $table->string('status')->default('pending'); // pending, active, revoked
                $table->boolean('can_manage_stock')->default(false);
                $table->boolean('can_manage_expense')->default(false);
                $table->boolean('can_manage_sales')->default(false);
                $table->string('invitation_token', 64)->nullable()->unique();
                $table->timestamp('invitation_sent_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['outlet_id', 'user_id', 'role'], 'outlet_user_role_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_user_roles');
    }
};
