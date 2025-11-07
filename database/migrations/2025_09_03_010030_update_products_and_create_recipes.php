<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add fields to products if not present
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('products', 'sell_price')) {
                $table->decimal('sell_price', 14, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('products', 'cogs_method')) {
                $table->enum('cogs_method', ['recipe_average', 'manual'])->default('recipe_average')->after('sell_price');
            }
            if (!Schema::hasColumn('products', 'active')) {
                $table->boolean('active')->default(true)->after('cogs_method');
            }
        });

        if (!Schema::hasTable('product_recipes')) {
            Schema::create('product_recipes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->decimal('yield_qty', 14, 4)->default(1);
                $table->string('unit')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('product_recipe_items')) {
            Schema::create('product_recipe_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_recipe_id');
                $table->unsignedBigInteger('raw_material_id');
                $table->decimal('qty_per_yield', 14, 4);
                $table->decimal('waste_pct', 5, 2)->default(0);
                $table->timestamps();

                $table->foreign('product_recipe_id')->references('id')->on('product_recipes')->onDelete('cascade');
                $table->foreign('raw_material_id')->references('id')->on('raw_materials')->onDelete('restrict');
                $table->index(['product_recipe_id']);
                $table->index(['raw_material_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sku')) {
                $table->dropUnique(['sku']);
                $table->dropColumn('sku');
            }
            if (Schema::hasColumn('products', 'sell_price')) {
                $table->dropColumn('sell_price');
            }
            if (Schema::hasColumn('products', 'cogs_method')) {
                $table->dropColumn('cogs_method');
            }
            if (Schema::hasColumn('products', 'active')) {
                $table->dropColumn('active');
            }
        });

        Schema::dropIfExists('product_recipe_items');
        Schema::dropIfExists('product_recipes');
    }
};

