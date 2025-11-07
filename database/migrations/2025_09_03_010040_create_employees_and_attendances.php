<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('pin'); // hashed
                $table->enum('role', ['owner','manager','staff'])->default('staff');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->dateTime('clock_in_at');
                $table->decimal('clock_in_lat', 10, 7)->nullable();
                $table->decimal('clock_in_lng', 10, 7)->nullable();
                $table->string('clock_in_photo_path')->nullable();
                $table->dateTime('clock_out_at')->nullable();
                $table->decimal('clock_out_lat', 10, 7)->nullable();
                $table->decimal('clock_out_lng', 10, 7)->nullable();
                $table->string('clock_out_photo_path')->nullable();
                $table->text('notes')->nullable();
                $table->integer('work_minutes')->nullable();
                $table->timestamps();

                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->index(['employee_id']);
                $table->index(['clock_in_at']);
                $table->index(['clock_out_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');
    }
};

