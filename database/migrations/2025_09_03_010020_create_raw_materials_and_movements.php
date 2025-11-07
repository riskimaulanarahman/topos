<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('raw_materials')) {
            Schema::create('raw_materials', function (Blueprint $table) {
                $table->id();
                $table->string('sku')->unique();
                $table->string('name');
                $table->enum('unit', ['g','ml','pcs','kg','l']);
                $table->decimal('unit_cost', 14, 4)->default(0);
                $table->decimal('stock_qty', 14, 4)->default(0);
                $table->decimal('min_stock', 14, 4)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('raw_material_movements')) {
            Schema::create('raw_material_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('raw_material_id');
                $table->enum('type', ['adjustment','purchase','production_consume','return']);
                $table->decimal('qty_change', 14, 4); // signed
                $table->decimal('unit_cost', 14, 4)->default(0);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('notes')->nullable();
                $table->dateTime('occurred_at');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('raw_material_id')->references('id')->on('raw_materials')->onDelete('cascade');
                $table->index(['raw_material_id']);
                $table->index(['occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_movements');
        Schema::dropIfExists('raw_materials');
    }
};

