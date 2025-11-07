<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('raw_materials')) {
            return;
        }

        Schema::table('raw_materials', function (Blueprint $table) {
            if (! Schema::hasColumn('raw_materials', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('min_stock');
            }
            if (! Schema::hasColumn('raw_materials', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('raw_materials')) {
            return;
        }

        Schema::table('raw_materials', function (Blueprint $table) {
            if (Schema::hasColumn('raw_materials', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
            if (Schema::hasColumn('raw_materials', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};
