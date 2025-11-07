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
        Schema::table('raw_material_movements', function (Blueprint $table) {
            $table->decimal('counted_qty', 15, 4)->nullable()->after('qty_change');
            $table->string('adjustment_reason', 50)->nullable()->after('counted_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_material_movements', function (Blueprint $table) {
            $table->dropColumn(['counted_qty', 'adjustment_reason']);
        });
    }
};
