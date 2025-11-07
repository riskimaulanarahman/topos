<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('outlet_user_roles')) {
            return;
        }

        Schema::table('outlet_user_roles', function (Blueprint $table) {
            if (! Schema::hasColumn('outlet_user_roles', 'pin_hash')) {
                $table->string('pin_hash')->nullable()->after('can_manage_sales');
            }

            if (! Schema::hasColumn('outlet_user_roles', 'pin_last_set_at')) {
                $table->timestamp('pin_last_set_at')->nullable()->after('pin_hash');
            }

            if (! Schema::hasColumn('outlet_user_roles', 'pin_last_verified_at')) {
                $table->timestamp('pin_last_verified_at')->nullable()->after('pin_last_set_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('outlet_user_roles')) {
            return;
        }

        Schema::table('outlet_user_roles', function (Blueprint $table) {
            if (Schema::hasColumn('outlet_user_roles', 'pin_last_verified_at')) {
                $table->dropColumn('pin_last_verified_at');
            }

            if (Schema::hasColumn('outlet_user_roles', 'pin_last_set_at')) {
                $table->dropColumn('pin_last_set_at');
            }

            if (Schema::hasColumn('outlet_user_roles', 'pin_hash')) {
                $table->dropColumn('pin_hash');
            }
        });
    }
};

