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
        Schema::create('order_temporaries', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->integer('sub_total')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('tax')->default(0);
            $table->integer('service_charge')->default(0);
            $table->integer('total_price')->default(0);
            $table->integer('total_item')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_temporaries');
    }
};
