<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('option_items', function (Blueprint $table) {
            if (!Schema::hasColumn('option_items', 'product_id')) {
                $table->unsignedBigInteger('product_id')
                    ->nullable()
                    ->after('option_group_id');
                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('option_items', function (Blueprint $table) {
            if (Schema::hasColumn('option_items', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }
        });
    }
};
