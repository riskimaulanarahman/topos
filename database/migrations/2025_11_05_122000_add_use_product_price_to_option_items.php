<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('option_items', function (Blueprint $table) {
            if (!Schema::hasColumn('option_items', 'use_product_price')) {
                $table->boolean('use_product_price')
                    ->default(false)
                    ->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('option_items', function (Blueprint $table) {
            if (Schema::hasColumn('option_items', 'use_product_price')) {
                $table->dropColumn('use_product_price');
            }
        });
    }
};
