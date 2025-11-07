<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('raw_materials')) {
            return;
        }

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->string('unit', 50)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('raw_materials')) {
            return;
        }

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->enum('unit', ['g','ml','pcs','kg','l'])->change();
        });
    }
};
