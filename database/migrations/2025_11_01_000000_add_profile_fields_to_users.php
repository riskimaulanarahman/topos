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
        Schema::table('users', function (Blueprint $table) {
            $table->string('store_logo_path')->nullable()->after('phone');
            $table->text('store_description')->nullable()->after('store_logo_path');
            $table->json('operating_hours')->nullable()->after('store_description');
            $table->json('store_addresses')->nullable()->after('operating_hours');
            $table->json('map_links')->nullable()->after('store_addresses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'store_logo_path',
                'store_description',
                'operating_hours',
                'store_addresses',
                'map_links',
            ]);
        });
    }
};

