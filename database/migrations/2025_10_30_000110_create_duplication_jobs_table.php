<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('duplication_jobs')) {
            Schema::create('duplication_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('source_outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignId('target_outlet_id')->constrained('outlets')->cascadeOnDelete();
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->string('status', 30)->index();
                $table->json('requested_resources')->nullable();
                $table->json('options')->nullable();
                $table->json('counts')->nullable();
                $table->json('error_log')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('duplication_job_items')) {
            Schema::create('duplication_job_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('duplication_job_id')->constrained('duplication_jobs')->cascadeOnDelete();
                $table->string('entity_type', 40);
                $table->unsignedBigInteger('source_id');
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('status', 30)->index();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['duplication_job_id', 'entity_type']);
                $table->index(['entity_type', 'source_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('duplication_job_items');
        Schema::dropIfExists('duplication_jobs');
    }
};

