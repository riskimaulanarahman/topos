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
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (! Schema::hasColumn('categories', 'outlet_id')) {
                    $column = $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();

                    if (Schema::hasColumn('categories', 'user_id')) {
                        $column->after('user_id');
                    }
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'outlet_id')) {
                    $column = $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();

                    if (Schema::hasColumn('products', 'user_id')) {
                        $column->after('user_id');
                    }
                }
            });
        }

        if (Schema::hasTable('raw_materials')) {
            Schema::table('raw_materials', function (Blueprint $table) {
                if (! Schema::hasColumn('raw_materials', 'outlet_id')) {
                    $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                if (! Schema::hasColumn('expenses', 'outlet_id')) {
                    $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                if (Schema::hasColumn('expenses', 'outlet_id')) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                }
            });
        }

        if (Schema::hasTable('raw_materials')) {
            Schema::table('raw_materials', function (Blueprint $table) {
                if (Schema::hasColumn('raw_materials', 'outlet_id')) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'outlet_id')) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                }
            });
        }

        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (Schema::hasColumn('categories', 'outlet_id')) {
                    $table->dropForeign(['outlet_id']);
                    $table->dropColumn('outlet_id');
                }
            });
        }
    }
};
