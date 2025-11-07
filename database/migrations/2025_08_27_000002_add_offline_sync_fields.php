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
        // Add sync fields to categories table
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'sync_status')) {
                $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced')->after('updated_at');
                $table->timestamp('last_synced')->nullable()->after('sync_status');
                $table->string('client_version')->nullable()->after('last_synced');
                $table->bigInteger('version_id')->default(1)->after('client_version');
            }
        });

        // Add sync fields to products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sync_status')) {
                $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced')->after('updated_at');
                $table->timestamp('last_synced')->nullable()->after('sync_status');
                $table->string('client_version')->nullable()->after('last_synced');
                $table->bigInteger('version_id')->default(1)->after('client_version');
            }
        });

        // Add sync fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'sync_status')) {
                $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced')->after('updated_at');
                $table->timestamp('last_synced')->nullable()->after('sync_status');
                $table->string('client_version')->nullable()->after('last_synced');
                $table->bigInteger('version_id')->default(1)->after('client_version');
            }
        });

        // Add sync fields to other tables if they exist
        if (Schema::hasTable('discounts')) {
            Schema::table('discounts', function (Blueprint $table) {
                if (!Schema::hasColumn('discounts', 'sync_status')) {
                    $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced')->after('updated_at');
                    $table->timestamp('last_synced')->nullable()->after('sync_status');
                    $table->string('client_version')->nullable()->after('last_synced');
                    $table->bigInteger('version_id')->default(1)->after('client_version');
                }
            });
        }

        if (Schema::hasTable('additional_charges')) {
            Schema::table('additional_charges', function (Blueprint $table) {
                if (!Schema::hasColumn('additional_charges', 'sync_status')) {
                    $table->enum('sync_status', ['synced', 'pending', 'conflict'])->default('synced')->after('updated_at');
                    $table->timestamp('last_synced')->nullable()->after('sync_status');
                    $table->string('client_version')->nullable()->after('last_synced');
                    $table->bigInteger('version_id')->default(1)->after('client_version');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove sync fields from categories
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'sync_status')) {
                $table->dropColumn('sync_status');
            }
            if (Schema::hasColumn('categories', 'last_synced')) {
                $table->dropColumn('last_synced');
            }
            if (Schema::hasColumn('categories', 'client_version')) {
                $table->dropColumn('client_version');
            }
            if (Schema::hasColumn('categories', 'version_id')) {
                $table->dropColumn('version_id');
            }
        });

        // Remove sync fields from products
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sync_status')) {
                $table->dropColumn('sync_status');
            }
            if (Schema::hasColumn('products', 'last_synced')) {
                $table->dropColumn('last_synced');
            }
            if (Schema::hasColumn('products', 'client_version')) {
                $table->dropColumn('client_version');
            }
            if (Schema::hasColumn('products', 'version_id')) {
                $table->dropColumn('version_id');
            }
        });

        // Remove sync fields from orders
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'sync_status')) {
                $table->dropColumn('sync_status');
            }
            if (Schema::hasColumn('orders', 'last_synced')) {
                $table->dropColumn('last_synced');
            }
            if (Schema::hasColumn('orders', 'client_version')) {
                $table->dropColumn('client_version');
            }
            if (Schema::hasColumn('orders', 'version_id')) {
                $table->dropColumn('version_id');
            }
        });

        // Remove sync fields from other tables
        if (Schema::hasTable('discounts')) {
            Schema::table('discounts', function (Blueprint $table) {
                if (Schema::hasColumn('discounts', 'sync_status')) {
                    $table->dropColumn('sync_status');
                }
                if (Schema::hasColumn('discounts', 'last_synced')) {
                    $table->dropColumn('last_synced');
                }
                if (Schema::hasColumn('discounts', 'client_version')) {
                    $table->dropColumn('client_version');
                }
                if (Schema::hasColumn('discounts', 'version_id')) {
                    $table->dropColumn('version_id');
                }
            });
        }

        if (Schema::hasTable('additional_charges')) {
            Schema::table('additional_charges', function (Blueprint $table) {
                if (Schema::hasColumn('additional_charges', 'sync_status')) {
                    $table->dropColumn('sync_status');
                }
                if (Schema::hasColumn('additional_charges', 'last_synced')) {
                    $table->dropColumn('last_synced');
                }
                if (Schema::hasColumn('additional_charges', 'client_version')) {
                    $table->dropColumn('client_version');
                }
                if (Schema::hasColumn('additional_charges', 'version_id')) {
                    $table->dropColumn('version_id');
                }
            });
        }
    }
};
