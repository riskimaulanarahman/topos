<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('incomes')) {
            Schema::create('incomes', function (Blueprint $table) {
                $table->id();
                $table->date('date')->index();
                $table->string('reference_no')->unique();
                $table->decimal('amount', 14, 2);
                $table->unsignedBigInteger('category_id')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['category_id']);
            });
        } else {
            // Alter existing table to conform to spec
            Schema::table('incomes', function (Blueprint $table) {
                // Drop legacy columns if exist
                if (Schema::hasColumn('incomes', 'qty')) {
                    $table->dropColumn('qty');
                }
                if (Schema::hasColumn('incomes', 'price_per_unit')) {
                    $table->dropColumn('price_per_unit');
                }
                if (Schema::hasColumn('incomes', 'total')) {
                    $table->dropColumn('total');
                }
                if (Schema::hasColumn('incomes', 'payment_type')) {
                    $table->dropColumn('payment_type');
                }

                if (!Schema::hasColumn('incomes', 'reference_no')) {
                    $table->string('reference_no')->unique()->after('date');
                }
                if (!Schema::hasColumn('incomes', 'amount')) {
                    $table->decimal('amount', 14, 2)->after('reference_no');
                } else {
                    // Ensure correct precision
                    DB::statement('ALTER TABLE incomes MODIFY amount DECIMAL(14,2)');
                }
                if (!Schema::hasColumn('incomes', 'category_id')) {
                    $table->unsignedBigInteger('category_id')->nullable()->after('amount');
                }
                if (!Schema::hasColumn('incomes', 'notes')) {
                    $table->text('notes')->nullable()->after('category_id');
                }
                if (!Schema::hasColumn('incomes', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('notes');
                }
                if (!Schema::hasColumn('incomes', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
                if (!Schema::hasColumn('incomes', 'deleted_at')) {
                    $table->softDeletes();
                }
                $table->index(['category_id']);
            });
        }

        // Income categories
        if (!Schema::hasTable('income_categories')) {
            Schema::create('income_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // We won't drop incomes table to avoid data loss; just revert added columns if needed
        if (Schema::hasTable('incomes')) {
            Schema::table('incomes', function (Blueprint $table) {
                if (Schema::hasColumn('incomes', 'reference_no')) {
                    $table->dropUnique(['reference_no']);
                    $table->dropColumn('reference_no');
                }
                if (Schema::hasColumn('incomes', 'category_id')) {
                    $table->dropIndex(['category_id']);
                    $table->dropColumn('category_id');
                }
                if (Schema::hasColumn('incomes', 'notes')) {
                    $table->dropColumn('notes');
                }
                if (Schema::hasColumn('incomes', 'created_by')) {
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('incomes', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
                if (Schema::hasColumn('incomes', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }

        Schema::dropIfExists('income_categories');
    }
};

