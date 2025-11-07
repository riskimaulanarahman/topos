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
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'unit_price_before_discount')) {
                $table->integer('unit_price_before_discount')
                    ->default(0)
                    ->after('quantity');
            }

            if (! Schema::hasColumn('order_items', 'unit_price_after_discount')) {
                $table->integer('unit_price_after_discount')
                    ->default(0)
                    ->after('unit_price_before_discount');
            }

            if (! Schema::hasColumn('order_items', 'discount_amount')) {
                $table->integer('discount_amount')
                    ->default(0)
                    ->after('unit_price_after_discount');
            }

            if (! Schema::hasColumn('order_items', 'applied_discount_type')) {
                $table->enum('applied_discount_type', ['percentage', 'fixed'])
                    ->nullable()
                    ->after('discount_amount');
            }

            if (! Schema::hasColumn('order_items', 'applied_discount_value')) {
                $table->decimal('applied_discount_value', 15, 2)
                    ->nullable()
                    ->after('applied_discount_type');
            }

            if (! Schema::hasColumn('order_items', 'applied_discount_id')) {
                $table->foreignId('applied_discount_id')
                    ->nullable()
                    ->after('applied_discount_value')
                    ->constrained('discounts')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'applied_discount_id')) {
                $table->dropForeign(['applied_discount_id']);
                $table->dropColumn('applied_discount_id');
            }

            if (Schema::hasColumn('order_items', 'applied_discount_value')) {
                $table->dropColumn('applied_discount_value');
            }

            if (Schema::hasColumn('order_items', 'applied_discount_type')) {
                $table->dropColumn('applied_discount_type');
            }

            if (Schema::hasColumn('order_items', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }

            if (Schema::hasColumn('order_items', 'unit_price_after_discount')) {
                $table->dropColumn('unit_price_after_discount');
            }

            if (Schema::hasColumn('order_items', 'unit_price_before_discount')) {
                $table->dropColumn('unit_price_before_discount');
            }
        });
    }
};
