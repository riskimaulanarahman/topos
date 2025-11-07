<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('option_groups')) {
            DB::statement("ALTER TABLE option_groups MODIFY COLUMN type ENUM('variant','addon','modifier') NOT NULL");
        }

        if (! Schema::hasTable('order_item_modifiers')) {
            Schema::create('order_item_modifiers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_item_id');
                $table->unsignedBigInteger('option_item_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('outlet_id')->nullable();
                $table->string('modifier_group_name');
                $table->string('modifier_name');
                $table->integer('price_adjustment')->default(0);
                $table->unsignedInteger('quantity')->default(1);
                $table->timestamps();

                $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
                $table->foreign('option_item_id')->references('id')->on('option_items')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
                $table->index(['order_item_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_modifiers');

        if (Schema::hasTable('option_groups')) {
            DB::statement("ALTER TABLE option_groups MODIFY COLUMN type ENUM('variant','addon') NOT NULL");
        }
    }
};

