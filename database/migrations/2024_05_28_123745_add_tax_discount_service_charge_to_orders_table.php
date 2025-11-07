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

            $table->integer('tax')->after('total_price')->default(0);
            $table->integer('discount')->after('tax')->default(0);
            $table->integer('service_charge')->after('discount')->default(0);
            $table->integer('sub_total')->after('service_charge')->default(0);
            $table->integer('payment_amount')->after('sub_total')->default(0);
            $table->string('cashier_name')->after('payment_amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tax', 'discount', 'service_charge', 'sub_total', 'payment_amount', 'cashier_name']);
        });
    }
};
