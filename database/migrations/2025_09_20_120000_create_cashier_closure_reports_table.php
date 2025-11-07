<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_closure_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_session_id')->constrained()->cascadeOnDelete();
            $table->json('summary');
            $table->string('email_to')->nullable();
            $table->string('email_status')->default('pending');
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->text('email_error')->nullable();
            $table->timestamps();

            $table->unique('cashier_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_closure_reports');
    }
};
