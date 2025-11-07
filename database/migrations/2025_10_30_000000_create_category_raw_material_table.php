<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_raw_material')) {
            Schema::create('category_raw_material', function (Blueprint $table) {
                $table->id();
                $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnDelete();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['raw_material_id', 'category_id'], 'category_raw_material_unique');
                $table->index('category_id');
                $table->index('raw_material_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_raw_material');
    }
};
