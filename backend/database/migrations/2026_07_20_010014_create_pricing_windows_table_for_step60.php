<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_inventory') && Schema::hasColumn('ticket_inventory', 'pricing_window_id')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->dropForeign(['pricing_window_id']);
            });
        }

        if (Schema::hasTable('analytics_sales_timeline') && Schema::hasColumn('analytics_sales_timeline', 'pricing_window_id')) {
            Schema::table('analytics_sales_timeline', function (Blueprint $table) {
                $table->dropForeign(['pricing_window_id']);
            });
        }

        Schema::dropIfExists('pricing_windows');

        Schema::create('pricing_windows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('ticket_category_id');
            $table->string('window_name', 100);
            $table->timestamp('start_date_time');
            $table->timestamp('end_date_time');
            $table->decimal('price', 10, 2);
            $table->integer('quantity_limit')->nullable();
            $table->integer('quantity_sold')->default(0);
            $table->boolean('is_active')->default(false);
            $table->integer('priority')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('event_id');
            $table->index('ticket_category_id');
            $table->index(['event_id', 'ticket_category_id']);
            $table->index('start_date_time');
            $table->index('end_date_time');
            $table->index('deleted_at');
            $table->index(['is_active', 'start_date_time', 'end_date_time'], 'idx_windows_active_daterange');
            $table->index(['event_id', 'is_active'], 'idx_windows_event_active');

            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('ticket_category_id')->references('id')->on('ticket_tiers')->onDelete('set null');
        });

        if (Schema::hasTable('ticket_inventory') && Schema::hasColumn('ticket_inventory', 'pricing_window_id')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->foreignUuid('pricing_window_id')->nullable()->constrained('pricing_windows')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('analytics_sales_timeline') && Schema::hasColumn('analytics_sales_timeline', 'pricing_window_id')) {
            Schema::table('analytics_sales_timeline', function (Blueprint $table) {
                $table->foreignUuid('pricing_window_id')->nullable()->constrained('pricing_windows')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ticket_inventory') && Schema::hasColumn('ticket_inventory', 'pricing_window_id')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->dropForeign(['pricing_window_id']);
            });
        }

        if (Schema::hasTable('analytics_sales_timeline') && Schema::hasColumn('analytics_sales_timeline', 'pricing_window_id')) {
            Schema::table('analytics_sales_timeline', function (Blueprint $table) {
                $table->dropForeign(['pricing_window_id']);
            });
        }

        Schema::dropIfExists('pricing_windows');

        Schema::create('pricing_windows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->integer('priority')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (Schema::hasTable('ticket_inventory') && Schema::hasColumn('ticket_inventory', 'pricing_window_id')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->foreignUuid('pricing_window_id')->nullable()->constrained('pricing_windows')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('analytics_sales_timeline') && Schema::hasColumn('analytics_sales_timeline', 'pricing_window_id')) {
            Schema::table('analytics_sales_timeline', function (Blueprint $table) {
                $table->foreignUuid('pricing_window_id')->nullable()->constrained('pricing_windows')->onDelete('set null');
            });
        }
    }
};