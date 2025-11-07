<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('raw_material_transfers')) {
            Schema::create('raw_material_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('raw_material_from_id')->constrained('raw_materials')->cascadeOnDelete();
                $table->foreignId('raw_material_to_id')->constrained('raw_materials')->cascadeOnDelete();
                $table->foreignId('outlet_from_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignId('outlet_to_id')->constrained('outlets')->cascadeOnDelete();
                $table->decimal('qty', 14, 4);
                $table->text('notes')->nullable();
                $table->foreignId('movement_out_id')->nullable()->constrained('raw_material_movements')->nullOnDelete();
                $table->foreignId('movement_in_id')->nullable()->constrained('raw_material_movements')->nullOnDelete();
                $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('transferred_at')->nullable();
                $table->timestamps();

                $table->index(['raw_material_from_id', 'transferred_at'], 'rm_transfers_from_idx');
                $table->index(['raw_material_to_id', 'transferred_at'], 'rm_transfers_to_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_transfers');
    }
};

