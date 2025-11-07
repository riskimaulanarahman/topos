<?php

use App\Models\Outlet;
use App\Models\OutletUserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('outlets') || ! Schema::hasTable('outlet_user_roles')) {
            return;
        }

        User::query()->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $existingOutletId = OutletUserRole::where('user_id', $user->id)
                    ->where('role', 'owner')
                    ->value('outlet_id');

                if ($existingOutletId) {
                    $outletId = $existingOutletId;
                } else {
                    $outlet = Outlet::create([
                        'name' => $user->store_name ?: ($user->name ?: 'Outlet ' . $user->id),
                        'created_by' => $user->id,
                    ]);

                    OutletUserRole::create([
                        'outlet_id' => $outlet->id,
                        'user_id' => $user->id,
                        'role' => 'owner',
                        'status' => 'active',
                        'can_manage_stock' => true,
                        'can_manage_expense' => true,
                        'can_manage_sales' => true,
                        'accepted_at' => now(),
                        'created_by' => $user->id,
                    ]);

                    $outletId = $outlet->id;
                }

                if (! $outletId) {
                    continue;
                }

                DB::table('categories')->where('user_id', $user->id)->whereNull('outlet_id')->update(['outlet_id' => $outletId]);
                DB::table('products')->where('user_id', $user->id)->whereNull('outlet_id')->update(['outlet_id' => $outletId]);
                if (Schema::hasColumn('raw_materials', 'created_by')) {
                    DB::table('raw_materials')->where('created_by', $user->id)->whereNull('outlet_id')->update(['outlet_id' => $outletId]);
                }
                if (Schema::hasColumn('expenses', 'created_by')) {
                    DB::table('expenses')->where('created_by', $user->id)->whereNull('outlet_id')->update(['outlet_id' => $outletId]);
                }
            }
        });
    }

    public function down(): void
    {
        // No rollback for seeded default outlets
    }
};
