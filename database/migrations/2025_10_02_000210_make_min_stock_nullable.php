<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('raw_materials')) {
            Schema::table('raw_materials', function (Blueprint $table) {
                $table->decimal('min_stock', 14, 4)->nullable()->default(null)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('raw_materials')) {
            Schema::table('raw_materials', function (Blueprint $table) {
                $table->decimal('min_stock', 14, 4)->default(0)->nullable(false)->change();
            });
        }
    }
};
