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
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'table_number')) {
                $table->string('table_number')->nullable()->after('transaction_number');
            }

            if (! Schema::hasColumn('orders', 'order_number')) {
                $table->integer('order_number')->nullable()->after('table_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'order_number')) {
                $table->dropColumn('order_number');
            }

            if (Schema::hasColumn('orders', 'table_number')) {
                $table->dropColumn('table_number');
            }
        });
    }
};
