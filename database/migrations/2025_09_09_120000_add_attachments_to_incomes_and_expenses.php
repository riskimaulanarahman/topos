<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('incomes') && !Schema::hasColumn('incomes', 'attachment_path')) {
            Schema::table('incomes', function (Blueprint $table) {
                $table->string('attachment_path')->nullable()->after('notes');
            });
        }

        if (Schema::hasTable('expenses') && !Schema::hasColumn('expenses', 'attachment_path')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('attachment_path')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('incomes') && Schema::hasColumn('incomes', 'attachment_path')) {
            Schema::table('incomes', function (Blueprint $table) {
                $table->dropColumn('attachment_path');
            });
        }

        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'attachment_path')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('attachment_path');
            });
        }
    }
};

