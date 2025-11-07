<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('outlets') && Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'outlet_id')) {
                    $column = $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();

                    if (Schema::hasColumn('orders', 'user_id')) {
                        $column->after('user_id');
                    }
                }
            });

            // Backfill outlet_id for existing orders based on owner assignment
            if (Schema::hasColumn('orders', 'outlet_id')) {
                $ownerRoles = DB::table('outlet_user_roles')
                    ->select('outlet_id', 'user_id')
                    ->where('role', 'owner')
                    ->get()
                    ->groupBy('user_id')
                    ->map(fn ($rows) => $rows->pluck('outlet_id')->first());

                if ($ownerRoles->isNotEmpty()) {
                    foreach ($ownerRoles as $userId => $outletId) {
                        DB::table('orders')
                            ->whereNull('outlet_id')
                            ->where('user_id', $userId)
                            ->update(['outlet_id' => $outletId]);
                    }
                }
            }
        }

        if (Schema::hasTable('outlets') && Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('order_items', 'outlet_id')) {
                    $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete()->after('order_id');
                }
            });

            if (Schema::hasColumn('order_items', 'outlet_id') && Schema::hasTable('orders') && Schema::hasColumn('orders', 'outlet_id')) {
                DB::table('order_items')
                    ->select('order_items.id', 'orders.outlet_id')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereNull('order_items.outlet_id')
                    ->whereNotNull('orders.outlet_id')
                    ->orderBy('order_items.id')
                    ->chunk(500, function ($rows) {
                        foreach ($rows as $row) {
                            DB::table('order_items')
                                ->where('id', $row->id)
                                ->update(['outlet_id' => $row->outlet_id]);
                        }
                    }, 'order_items.id');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'outlet_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropForeign(['outlet_id']);
                $table->dropColumn('outlet_id');
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'outlet_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['outlet_id']);
                $table->dropColumn('outlet_id');
            });
        }
    }
};
