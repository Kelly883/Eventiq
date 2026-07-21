<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            // 1. Add tier_order for manual sequencing
            $table->unsignedInteger('tier_order')->nullable()->after('name')->default(0);

            // 2. Add status field for workflow management
            $table->string('status')->default('draft')->after('is_active');

            // 3. Add currency field for multi-currency support
            $table->string('currency', 3)->default('USD')->after('tier_image_url');

            // 4. Add voucher_code for tier-specific discounts
            $table->string('voucher_code')->nullable()->after('currency');

            // 5. Add sales_channel for multi-channel selling
            $table->string('sales_channel')->nullable()->after('voucher_code');

            // 6. Add published_at timestamp
            $table->timestamp('published_at')->nullable()->after('sales_channel');

            // 7. Add audit fields
            $table->unsignedBigInteger('created_by')->nullable()->after('updated_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            // 8. Add soft deletes
            $table->softDeletes()->after('updated_at');
        });

        // Add foreign keys for audit fields (requires users table)
        if (Schema::hasTable('users')) {
            Schema::table('ticket_tiers', function (Blueprint $table) {
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // 9. Add check constraints (SQLite/MySQL compatible)
        try {
            if (DB::getDriverName() === 'sqlite') {
                // SQLite supports check constraints
                Schema::table('ticket_tiers', function (Blueprint $table) {
                    $table->check('price_positive', DB::raw('price >= 0'));
                    $table->check('quantity_positive', DB::raw('quantity IS NULL OR quantity > 0'));
                    $table->check('max_per_customer_positive', DB::raw('max_per_customer IS NULL OR max_per_customer > 0'));
                    $table->check('valid_sales_dates', DB::raw('sales_end_date IS NULL OR sales_start_date IS NULL OR sales_end_date > sales_start_date'));
                    $table->check('valid_early_bird', DB::raw('early_bird_price IS NULL OR price IS NULL OR early_bird_price < price'));
                    $table->check('valid_purchase_limits', DB::raw('max_purchase IS NULL OR min_purchase <= max_purchase'));
                    $table->check('valid_tier_order', DB::raw('tier_order >= 0'));
                });
            }
        } catch (\Exception $e) {
            // Check constraints may not be supported on all databases
            // Continue without them - validation will be handled at application level
        }

        // 10. Add index on sales_start_date for cross-event queries
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->index('sales_start_date');
        });

        // 11. Add index on status for filtering
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->index('status');
        });

        // 12. Add composite index for published tiers lookup
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->index(['event_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropIndex(['ticket_tiers_sales_start_date_index']);
            $table->dropIndex(['ticket_tiers_status_index']);
            $table->dropIndex(['ticket_tiers_event_id_status_published_at_index']);
        });

        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'tier_order',
                'status',
                'currency',
                'voucher_code',
                'sales_channel',
                'published_at',
                'created_by',
                'updated_by',
                'deleted_at'
            ]);
        });
    }
};