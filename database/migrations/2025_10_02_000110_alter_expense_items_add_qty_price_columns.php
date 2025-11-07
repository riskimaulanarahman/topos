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
                if (! Schema::hasColumn('expense_items', 'qty')) {
                    $table->decimal('qty', 18, 4)->default(0)->after('unit');
                }
                if (! Schema::hasColumn('expense_items', 'unit_cost')) {
                    $table->decimal('unit_cost', 18, 4)->default(0)->after('qty');
                }
                if (! Schema::hasColumn('expense_items', 'total_cost')) {
                    $table->decimal('total_cost', 18, 2)->default(0)->after('unit_cost');
                }
                if (! Schema::hasColumn('expense_items', 'notes')) {
                    $table->text('notes')->nullable()->after('total_cost');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expense_items')) {
            Schema::table('expense_items', function (Blueprint $table) {
                foreach (['notes','total_cost','unit_cost','qty'] as $column) {
                    if (Schema::hasColumn('expense_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
