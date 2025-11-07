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
        if (! Schema::hasTable('partner_category_change_requests')) {
            Schema::create('partner_category_change_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('outlet_id');
                $table->unsignedBigInteger('target_outlet_user_role_id');
                $table->unsignedBigInteger('requested_by');
                $table->json('payload');
                $table->string('status')->default('pending'); // pending, approved, rejected
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();

                $table->foreign('outlet_id', 'pc_change_outlet_fk')->references('id')->on('outlets')->cascadeOnDelete();
                $table->foreign('target_outlet_user_role_id', 'pc_change_target_role_fk')->references('id')->on('outlet_user_roles')->cascadeOnDelete();
                $table->foreign('requested_by', 'pc_change_requested_by_fk')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('reviewed_by', 'pc_change_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_category_change_requests');
    }
};
