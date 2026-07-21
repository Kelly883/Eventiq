<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Production-readiness migration for ticket_tiers.
     *
     * Fixes:
     * 1. Converts available_count to a database-generated column (driven by quantity - sold_count)
     * 2. Enforces sold_count <= quantity with a DB-level CHECK constraint (not just try/catch)
     * 3. Adds CHECK to ensure sold_count >= 0
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // ──────────────────────────────────────────────
        // 1. Re-sync existing available_count values
        // ──────────────────────────────────────────────
        DB::statement('UPDATE ticket_tiers SET sold_count = 0 WHERE sold_count IS NULL');
        DB::statement('UPDATE ticket_tiers SET quantity = 0 WHERE quantity IS NULL');
        DB::statement('UPDATE ticket_tiers SET sold_count = quantity WHERE sold_count > quantity');

        // ──────────────────────────────────────────────
        // 2. Add CHECK constraint: sold_count <= quantity
        //    (robust — no try/catch swallowing)
        // ──────────────────────────────────────────────
        if ($driver === 'mysql') {
            // Drop existing constraint if it exists (from step65 migration)
            try {
                DB::statement('ALTER TABLE ticket_tiers DROP CHECK ticket_tiers_sold_count_check');
            } catch (\Exception $e) {
                // May not exist if step65 constraint creation failed
            }
            DB::statement('ALTER TABLE ticket_tiers ADD CONSTRAINT ticket_tiers_sold_count_ck CHECK (sold_count <= quantity)');
        } elseif ($driver === 'sqlite') {
            try {
                DB::statement('ALTER TABLE ticket_tiers DROP CONSTRAINT ticket_tiers_sold_count_check');
            } catch (\Exception $e) {
                // May not exist
            }
            // SQLite 3.46+ supports ALTER TABLE ADD CONSTRAINT for CHECK
            DB::statement('ALTER TABLE ticket_tiers ADD CONSTRAINT ticket_tiers_sold_count_ck CHECK (sold_count <= quantity)');
        }

        // ──────────────────────────────────────────────
        // 3. Add CHECK constraint: sold_count >= 0
        // ──────────────────────────────────────────────
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE ticket_tiers ADD CONSTRAINT ticket_tiers_sold_count_non_negative_ck CHECK (sold_count >= 0)');
        } elseif ($driver === 'sqlite') {
            DB::statement('ALTER TABLE ticket_tiers ADD CONSTRAINT ticket_tiers_sold_count_non_negative_ck CHECK (sold_count >= 0)');
        }

        // ──────────────────────────────────────────────
        // 4. Convert available_count to a generated column
        //
        //    available_count = MAX(0, quantity - sold_count)
        //
        //    For MySQL 8.0+:   GENERATED ALWAYS AS (GREATEST(0, quantity - sold_count)) STORED
        //    For SQLite 3.31+: GENERATED ALWAYS AS (MAX(0, quantity - sold_count)) STORED
        //    For PostgreSQL:   GENERATED ALWAYS AS (GREATEST(0, quantity - sold_count)) STORED
        //
        //    Note: SQLite does NOT support ALTER TABLE ... MODIFY COLUMN to change
        //    a regular column into a generated column. Instead we must:
        //       a) Create a new table with the generated column
        //       b) Copy data
        //       c) Drop old table
        //       d) Rename new table
        //
        //    For MySQL we use ALTER TABLE MODIFY COLUMN which is simpler.
        // ──────────────────────────────────────────────

        if ($driver === 'mysql') {
            // MySQL — clean ALTER TABLE MODIFY COLUMN with GENERATED ALWAYS
            DB::statement('ALTER TABLE ticket_tiers MODIFY COLUMN available_count INT UNSIGNED '
                . 'GENERATED ALWAYS AS (GREATEST(0, COALESCE(quantity, 0) - COALESCE(sold_count, 0))) STORED');
        } elseif ($driver === 'sqlite') {
            // SQLite — table recreation approach (SQLite 3.25+ supports ALTER TABLE RENAME COLUMN)
            // 1. Create new table with generated column
            Schema::create('ticket_tiers_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->unsignedInteger('tier_order')->nullable()->default(0);
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->unsignedInteger('quantity');
                $table->text('benefits_description')->nullable();
                $table->string('tier_image_url')->nullable();
                $table->integer('min_purchase')->default(1);
                $table->integer('max_purchase')->nullable();
                $table->decimal('early_bird_price', 10, 2)->nullable();
                $table->timestamp('early_bird_end_date')->nullable();
                $table->unsignedInteger('max_per_customer')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('status')->default('draft');
                $table->dateTime('sales_start_date')->nullable();
                $table->dateTime('sales_end_date')->nullable();
                $table->string('currency', 3)->default('USD');
                $table->string('voucher_code')->nullable();
                $table->string('sales_channel')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->unsignedInteger('sold_count')->default(0);
                // Generated column for SQLite
                $table->unsignedInteger('available_count')
                    ->virtualAs('MAX(0, COALESCE(quantity, 0) - COALESCE(sold_count, 0))');
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                // Indexes
                $table->index('event_id');
                $table->index(['event_id', 'sales_start_date']);
                $table->index('created_at');
                $table->index('sales_start_date');
                $table->index('status');
                $table->index(['event_id', 'status', 'published_at']);
                $table->index('is_active');
                $table->index('sales_end_date');
                $table->index('early_bird_end_date');
                $table->index(['event_id', 'is_active', 'sales_start_date']);
                $table->unique(['event_id', 'name']);
                $table->index('deleted_at', 'idx_ticket_tiers_deleted_at');
                $table->index(['event_id', 'deleted_at'], 'idx_ticket_tiers_event_deleted_at');
                $table->index(['event_id', 'id'], 'idx_ticket_tiers_event_id_partition');
                $table->index(['event_id', 'is_active', 'sales_start_date', 'sales_end_date'], 'idx_ticket_tiers_availability_check');
            });

            // 2. Copy data (exclude old available_count — the generated column handles it)
            DB::statement('INSERT INTO ticket_tiers_new (
                id, event_id, name, tier_order, description, price, quantity,
                benefits_description, tier_image_url, min_purchase, max_purchase,
                early_bird_price, early_bird_end_date, max_per_customer, is_active,
                status, sales_start_date, sales_end_date, currency, voucher_code,
                sales_channel, published_at, sold_count,
                created_at, updated_at, deleted_at, created_by, updated_by
            ) SELECT
                id, event_id, name, tier_order, description, price, quantity,
                benefits_description, tier_image_url, min_purchase, max_purchase,
                early_bird_price, early_bird_end_date, max_per_customer, is_active,
                status, sales_start_date, sales_end_date, currency, voucher_code,
                sales_channel, published_at, sold_count,
                created_at, updated_at, deleted_at, created_by, updated_by
            FROM ticket_tiers');

            // 3. Drop old table and rename
            Schema::drop('ticket_tiers');
            Schema::rename('ticket_tiers_new', 'ticket_tiers');
        }

        // ──────────────────────────────────────────────
        // 5. Add CHECK constraint: price > 0 (no free events with real tiers)
        //    Note: This is commented out because $0 tiers may be intentional.
        //    Uncomment if your business rules require paid events.
        // ──────────────────────────────────────────────
        // if ($driver === 'mysql') {
        //     DB::statement('ALTER TABLE ticket_tiers ADD CONSTRAINT ticket_tiers_price_positive_ck CHECK (price > 0)');
        // } elseif ($driver === 'sqlite') {
        //     DB::statement('ALTER TABLE ticket_tiers ADD CONSTRAINT ticket_tiers_price_positive_ck CHECK (price > 0)');
        // }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        // Drop CHECK constraints (MySQL only — SQLite requires table recreation)
        if ($driver === 'mysql') {
            try {
                DB::statement('ALTER TABLE ticket_tiers DROP CHECK ticket_tiers_sold_count_ck');
            } catch (\Exception $e) {}
            try {
                DB::statement('ALTER TABLE ticket_tiers DROP CHECK ticket_tiers_sold_count_non_negative_ck');
            } catch (\Exception $e) {}
        }

        // Convert available_count back to regular column (MySQL)
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE ticket_tiers MODIFY COLUMN available_count INT UNSIGNED DEFAULT 0');
        }

        // NOTE: SQLite down migration would require the same table recreation
        // approach to remove the VIRTUAL column. In practice, rolling back
        // this migration on SQLite is not supported — use a fresh migrate:fresh instead.
    }
};

