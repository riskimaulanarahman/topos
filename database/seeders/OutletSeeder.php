<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\OutletUserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OutletSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $outlet = Outlet::firstOrCreate(
                    [
                        'created_by' => $user->id,
                    ],
                    [
                        'name' => $user->store_name ?: ($user->name ?: 'Outlet ' . $user->id),
                        'code' => Str::upper(Str::slug($user->store_name ?: $user->name ?: 'Outlet-' . $user->id, '')),
                        'address' => null,
                        'notes' => null,
                    ]
                );

                OutletUserRole::firstOrCreate(
                    [
                        'outlet_id' => $outlet->id,
                        'user_id' => $user->id,
                        'role' => 'owner',
                    ],
                    [
                        'status' => 'active',
                        'can_manage_stock' => true,
                        'can_manage_expense' => true,
                        'can_manage_sales' => true,
                        'accepted_at' => now(),
                        'created_by' => $user->id,
                    ]
                );

                if (Schema::hasTable('categories')) {
                    DB::table('categories')
                        ->where('user_id', $user->id)
                        ->update(['outlet_id' => $outlet->id]);
                }

                if (Schema::hasTable('products')) {
                    DB::table('products')
                        ->where('user_id', $user->id)
                        ->update(['outlet_id' => $outlet->id]);
                }

                if (Schema::hasColumn('raw_materials', 'created_by')) {
                    DB::table('raw_materials')
                        ->where('created_by', $user->id)
                        ->update(['outlet_id' => $outlet->id]);
                }

                if (Schema::hasColumn('expenses', 'created_by')) {
                    DB::table('expenses')
                        ->where('created_by', $user->id)
                        ->update(['outlet_id' => $outlet->id]);
                }
            }
        });
    }
}

