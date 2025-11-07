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
            // Add missing fields if not exists
            if (!Schema::hasColumn('orders', 'transaction_number')) {
                $table->string('transaction_number')->nullable()->after('id');
            }
            
            if (!Schema::hasColumn('orders', 'nominal_bayar')) {
                $table->integer('nominal_bayar')->nullable()->after('payment_method');
            }
            
            if (!Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('completed')->after('nominal_bayar');
            }
            
            if (!Schema::hasColumn('orders', 'refund_method')) {
                $table->string('refund_method')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('orders', 'refund_nominal')) {
                $table->integer('refund_nominal')->nullable()->after('refund_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columnsToCheck = [
                'transaction_number',
                'nominal_bayar', 
                'status',
                'refund_method',
                'refund_nominal'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
