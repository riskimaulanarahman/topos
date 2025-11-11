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
        Schema::table('categories', function (Blueprint $table) {
            // Add parent_id column for hierarchical categories
            $table->foreignId('parent_id')->nullable()->after('outlet_id')->constrained('categories')->nullOnDelete();
            
            // Add index for better performance on parent-child queries
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['parent_id']);
            // Drop the column
            $table->dropColumn('parent_id');
        });
    }
};
