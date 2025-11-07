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
        Schema::table('discounts', function (Blueprint $table) {
            if (! Schema::hasColumn('discounts', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('discounts', 'outlet_id')) {
                $table->foreignId('outlet_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('discounts', 'scope')) {
                $table->string('scope', 50)
                    ->default('global')
                    ->after('status');
            }

            if (! Schema::hasColumn('discounts', 'auto_apply')) {
                $table->boolean('auto_apply')
                    ->default(false)
                    ->after('scope');
            }

            if (! Schema::hasColumn('discounts', 'priority')) {
                $table->integer('priority')
                    ->default(0)
                    ->after('auto_apply');
            }
        });

        if (! Schema::hasTable('discount_product_rules')) {
            Schema::create('discount_product_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
                $table->enum('type_override', ['percentage', 'fixed'])->nullable();
                $table->decimal('value_override', 15, 2)->nullable();
                $table->boolean('auto_apply')->default(false);
                $table->integer('priority')->default(0);
                $table->timestamp('valid_from')->nullable();
                $table->timestamp('valid_until')->nullable();
                $table->timestamps();

                $table->unique(['discount_id', 'product_id'], 'discount_product_unique');
                $table->index(['product_id', 'auto_apply', 'priority'], 'discount_product_auto');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('discount_product_rules')) {
            Schema::dropIfExists('discount_product_rules');
        }

        Schema::table('discounts', function (Blueprint $table) {
            if (Schema::hasColumn('discounts', 'priority')) {
                $table->dropColumn('priority');
            }

            if (Schema::hasColumn('discounts', 'auto_apply')) {
                $table->dropColumn('auto_apply');
            }

            if (Schema::hasColumn('discounts', 'scope')) {
                $table->dropColumn('scope');
            }

            if (Schema::hasColumn('discounts', 'outlet_id')) {
                $table->dropForeign(['outlet_id']);
                $table->dropColumn('outlet_id');
            }

            if (Schema::hasColumn('discounts', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
