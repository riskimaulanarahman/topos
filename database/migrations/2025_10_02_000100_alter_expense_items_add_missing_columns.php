<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expense_items')) {
            Schema::table('expense_items', function (Blueprint $table) {
                if (! Schema::hasColumn('expense_items', 'description')) {
                    $table->string('description')->nullable()->after('raw_material_id');
                }
                if (! Schema::hasColumn('expense_items', 'unit')) {
                    $table->string('unit', 50)->nullable()->after('description');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expense_items')) {
            Schema::table('expense_items', function (Blueprint $table) {
                if (Schema::hasColumn('expense_items', 'unit')) {
                    $table->dropColumn('unit');
                }
                if (Schema::hasColumn('expense_items', 'description')) {
                    $table->dropColumn('description');
                }
            });
        }
    }
};
