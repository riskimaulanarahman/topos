<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variant_groups')) {
            Schema::create('product_variant_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('outlet_id')->nullable();
                $table->string('name');
                $table->boolean('is_required')->default(true);
                $table->enum('selection_type', ['single', 'multiple'])->default('single');
                $table->unsignedInteger('min_select')->default(1);
                $table->unsignedInteger('max_select')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced');
                $table->timestamp('last_synced')->nullable();
                $table->string('client_version')->nullable();
                $table->unsignedBigInteger('version_id')->default(1);
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
                $table->index(['product_id', 'sort_order']);
                $table->index(['outlet_id']);
            });
        }

        if (! Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('variant_group_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('outlet_id')->nullable();
                $table->string('name');
                $table->integer('price_adjustment')->default(0);
                $table->boolean('is_default')->default(false);
                $table->integer('stock')->nullable();
                $table->string('sku')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced');
                $table->timestamp('last_synced')->nullable();
                $table->string('client_version')->nullable();
                $table->unsignedBigInteger('version_id')->default(1);
                $table->timestamps();

                $table->foreign('variant_group_id')->references('id')->on('product_variant_groups')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
                $table->index(['variant_group_id', 'is_active']);
                $table->index(['outlet_id']);
            });
        }

        if (! Schema::hasTable('product_addon_groups')) {
            Schema::create('product_addon_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('outlet_id')->nullable();
                $table->string('name');
                $table->boolean('is_required')->default(false);
                $table->unsignedInteger('min_select')->default(0);
                $table->unsignedInteger('max_select')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced');
                $table->timestamp('last_synced')->nullable();
                $table->string('client_version')->nullable();
                $table->unsignedBigInteger('version_id')->default(1);
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
                $table->index(['product_id', 'sort_order']);
                $table->index(['outlet_id']);
            });
        }

        if (! Schema::hasTable('product_addons')) {
            Schema::create('product_addons', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('addon_group_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('outlet_id')->nullable();
                $table->string('name');
                $table->integer('price_adjustment')->default(0);
                $table->unsignedInteger('max_quantity')->default(1);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced');
                $table->timestamp('last_synced')->nullable();
                $table->string('client_version')->nullable();
                $table->unsignedBigInteger('version_id')->default(1);
                $table->timestamps();

                $table->foreign('addon_group_id')->references('id')->on('product_addon_groups')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
                $table->index(['addon_group_id', 'is_active']);
                $table->index(['outlet_id']);
            });
        }

        if (! Schema::hasTable('order_item_variants')) {
            Schema::create('order_item_variants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_item_id');
                $table->unsignedBigInteger('product_variant_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('outlet_id')->nullable();
                $table->string('variant_group_name');
                $table->string('variant_name');
                $table->integer('price_adjustment')->default(0);
                $table->timestamps();

                $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
                $table->index(['order_item_id']);
            });
        }

        if (! Schema::hasTable('order_item_addons')) {
            Schema::create('order_item_addons', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_item_id');
                $table->unsignedBigInteger('product_addon_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('outlet_id')->nullable();
                $table->string('addon_group_name');
                $table->string('addon_name');
                $table->integer('price_adjustment')->default(0);
                $table->unsignedInteger('quantity')->default(1);
                $table->timestamps();

                $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
                $table->foreign('product_addon_id')->references('id')->on('product_addons')->nullOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
                $table->index(['order_item_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_addons');
        Schema::dropIfExists('order_item_variants');
        Schema::dropIfExists('product_addons');
        Schema::dropIfExists('product_addon_groups');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_variant_groups');
    }
};
