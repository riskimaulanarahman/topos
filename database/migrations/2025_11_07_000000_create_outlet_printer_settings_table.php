<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('outlet_printer_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->enum('paper_size', ['58', '80'])->default('58');
            $table->unsignedTinyInteger('title_font_size')->default(2);
            $table->boolean('show_logo')->default(false);
            $table->string('logo_path')->nullable();
            $table->boolean('show_footer')->default(false);
            $table->text('footer_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_printer_settings');
    }
};
