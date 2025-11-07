<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('option_groups')) {
            DB::statement("ALTER TABLE option_groups MODIFY COLUMN type ENUM('variant','addon','preference') NOT NULL");
            DB::statement("UPDATE option_groups SET type = 'preference' WHERE type = 'modifier'");
        }

        if (Schema::hasTable('order_item_modifiers')) {
            DB::statement("ALTER TABLE order_item_modifiers CHANGE modifier_group_name preference_group_name VARCHAR(255) NOT NULL");
            DB::statement("ALTER TABLE order_item_modifiers CHANGE modifier_name preference_name VARCHAR(255) NOT NULL");
            Schema::rename('order_item_modifiers', 'order_item_preferences');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_item_preferences')) {
            Schema::rename('order_item_preferences', 'order_item_modifiers');
            DB::statement("ALTER TABLE order_item_modifiers CHANGE preference_group_name modifier_group_name VARCHAR(255) NOT NULL");
            DB::statement("ALTER TABLE order_item_modifiers CHANGE preference_name modifier_name VARCHAR(255) NOT NULL");
        }

        if (Schema::hasTable('option_groups')) {
            DB::statement("UPDATE option_groups SET type = 'modifier' WHERE type = 'preference'");
            DB::statement("ALTER TABLE option_groups MODIFY COLUMN type ENUM('variant','addon','modifier') NOT NULL");
        }
    }
};
