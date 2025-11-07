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
        // Add user_id to products table if not exists
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });

        // Add user_id to categories table (already created but ensure consistency)
        if (!Schema::hasColumn('categories', 'user_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Add user_id to orders table (rename kasir_id to user_id for consistency)
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'kasir_id') && !Schema::hasColumn('orders', 'user_id')) {
                // Drop foreign key constraint
                $table->dropForeign(['kasir_id']);
                
                // Rename column
                $table->renameColumn('kasir_id', 'user_id');
                
                // Re-add foreign key constraint
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            } elseif (!Schema::hasColumn('orders', 'user_id') && !Schema::hasColumn('orders', 'kasir_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });

        // Add user_id to discounts table if exists
        if (Schema::hasTable('discounts')) {
            Schema::table('discounts', function (Blueprint $table) {
                if (!Schema::hasColumn('discounts', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                }
            });
        }

        // Add user_id to additional_charges table if exists
        if (Schema::hasTable('additional_charges')) {
            Schema::table('additional_charges', function (Blueprint $table) {
                if (!Schema::hasColumn('additional_charges', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                }
            });
        }

        // Add user_id to order_temporaries table if exists
        if (Schema::hasTable('order_temporaries')) {
            Schema::table('order_temporaries', function (Blueprint $table) {
                if (!Schema::hasColumn('order_temporaries', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('id');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove user_id from products
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        // Remove user_id from categories (keep it since it's needed)
        // Note: We don't remove from categories as it's needed for the app

        // Revert orders table changes
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->renameColumn('user_id', 'kasir_id');
                $table->foreign('kasir_id')->references('id')->on('users')->onDelete('cascade');
            }
        });

        // Remove user_id from discounts table
        if (Schema::hasTable('discounts')) {
            Schema::table('discounts', function (Blueprint $table) {
                if (Schema::hasColumn('discounts', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
            });
        }

        // Remove user_id from additional_charges table
        if (Schema::hasTable('additional_charges')) {
            Schema::table('additional_charges', function (Blueprint $table) {
                if (Schema::hasColumn('additional_charges', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
            });
        }

        // Remove user_id from order_temporaries table
        if (Schema::hasTable('order_temporaries')) {
            Schema::table('order_temporaries', function (Blueprint $table) {
                if (Schema::hasColumn('order_temporaries', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
            });
        }
    }
};
