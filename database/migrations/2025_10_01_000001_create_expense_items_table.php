<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expense_items')) {
            Schema::create('expense_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
                $table->foreignId('raw_material_id')->nullable()->constrained('raw_materials')->restrictOnDelete();
                $table->string('description')->nullable();
                $table->string('unit', 50)->nullable();
                $table->decimal('qty', 18, 4)->default(0);
                $table->decimal('unit_cost', 18, 4)->default(0);
                $table->decimal('total_cost', 18, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['expense_id']);
                $table->index(['raw_material_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
    }
};
