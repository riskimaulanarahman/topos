<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expense_items')) {
            return;
        }

        Schema::table('expense_items', function (Blueprint $table) {
            if (Schema::hasColumn('expense_items', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('expense_items', 'amount')) {
                $table->dropColumn('amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expense_items')) {
            return;
        }

        Schema::table('expense_items', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_items', 'name')) {
                $table->string('name')->nullable();
            }
            if (! Schema::hasColumn('expense_items', 'amount')) {
                $table->decimal('amount', 18, 2)->nullable();
            }
        });
    }
};
