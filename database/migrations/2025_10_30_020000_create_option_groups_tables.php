<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('option_groups')) {
            Schema::create('option_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('outlet_id')->nullable();
                $table->string('name');
                $table->enum('type', ['variant', 'addon']);
                $table->enum('selection_type', ['single', 'multiple'])->default('single');
                $table->boolean('is_required')->default(true);
                $table->unsignedInteger('min_select')->default(1);
                $table->unsignedInteger('max_select')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced');
                $table->timestamp('last_synced')->nullable();
                $table->string('client_version')->nullable();
                $table->unsignedBigInteger('version_id')->default(1);
                $table->timestamps();
                $table->unique(['type', 'name', 'user_id', 'outlet_id']);
                $table->index(['type', 'user_id', 'outlet_id']);
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('option_items')) {
            Schema::create('option_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('option_group_id');
                $table->string('name');
                $table->integer('price_adjustment')->default(0);
                $table->integer('stock')->nullable();
                $table->string('sku')->nullable();
                $table->unsignedInteger('max_quantity')->default(1);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced');
                $table->timestamp('last_synced')->nullable();
                $table->string('client_version')->nullable();
                $table->unsignedBigInteger('version_id')->default(1);
                $table->timestamps();

                $table->foreign('option_group_id')->references('id')->on('option_groups')->onDelete('cascade');
                $table->index(['option_group_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('product_option_groups')) {
            Schema::create('product_option_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('option_group_id');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_required_override')->nullable();
                $table->enum('selection_type_override', ['single', 'multiple'])->nullable();
                $table->unsignedInteger('min_select_override')->nullable();
                $table->unsignedInteger('max_select_override')->nullable();
                $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced');
                $table->timestamp('last_synced')->nullable();
                $table->string('client_version')->nullable();
                $table->unsignedBigInteger('version_id')->default(1);
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('option_group_id')->references('id')->on('option_groups')->onDelete('cascade');
                $table->unique(['product_id', 'option_group_id'], 'pog_product_group_unique');
                $table->index(['product_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('product_option_items')) {
            Schema::create('product_option_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_option_group_id');
                $table->unsignedBigInteger('option_item_id');
                $table->integer('price_adjustment_override')->nullable();
                $table->integer('stock_override')->nullable();
                $table->string('sku_override')->nullable();
                $table->unsignedInteger('max_quantity_override')->nullable();
                $table->boolean('is_default_override')->nullable();
                $table->boolean('is_active_override')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced');
                $table->timestamp('last_synced')->nullable();
                $table->string('client_version')->nullable();
                $table->unsignedBigInteger('version_id')->default(1);
                $table->timestamps();

                $table->foreign('product_option_group_id')->references('id')->on('product_option_groups')->onDelete('cascade');
                $table->foreign('option_item_id')->references('id')->on('option_items')->onDelete('cascade');
                $table->unique(['product_option_group_id', 'option_item_id'], 'poi_group_item_unique');
                $table->index(['product_option_group_id', 'sort_order']);
            });
        }

        if (! Schema::hasColumn('order_item_variants', 'option_item_id')) {
            Schema::table('order_item_variants', function (Blueprint $table) {
                $table->unsignedBigInteger('option_item_id')->nullable()->after('product_variant_id');
                $table->foreign('option_item_id')->references('id')->on('option_items')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('order_item_addons', 'option_item_id')) {
            Schema::table('order_item_addons', function (Blueprint $table) {
                $table->unsignedBigInteger('option_item_id')->nullable()->after('product_addon_id');
                $table->foreign('option_item_id')->references('id')->on('option_items')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('order_item_addons', function (Blueprint $table) {
            if (Schema::hasColumn('order_item_addons', 'option_item_id')) {
                $table->dropForeign(['option_item_id']);
                $table->dropColumn('option_item_id');
            }
        });

        Schema::table('order_item_variants', function (Blueprint $table) {
            if (Schema::hasColumn('order_item_variants', 'option_item_id')) {
                $table->dropForeign(['option_item_id']);
                $table->dropColumn('option_item_id');
            }
        });

        Schema::dropIfExists('product_option_items');
        Schema::dropIfExists('product_option_groups');
        Schema::dropIfExists('option_items');
        Schema::dropIfExists('option_groups');
    }
};
