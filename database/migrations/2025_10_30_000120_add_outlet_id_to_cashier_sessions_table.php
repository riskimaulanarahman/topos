<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cashier_sessions') && ! Schema::hasColumn('cashier_sessions', 'outlet_id')) {
            Schema::table('cashier_sessions', function (Blueprint $table) {
                $table->foreignId('outlet_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('outlets')
                    ->nullOnDelete();

                $table->index(['user_id', 'outlet_id', 'status'], 'cashier_sessions_user_outlet_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cashier_sessions') && Schema::hasColumn('cashier_sessions', 'outlet_id')) {
            Schema::table('cashier_sessions', function (Blueprint $table) {
                $table->dropIndex('cashier_sessions_user_outlet_status_idx');
                $table->dropConstrainedForeignId('outlet_id');
            });
        }
    }
};

