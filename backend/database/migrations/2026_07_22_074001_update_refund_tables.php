<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        // Update refund_requests table
        Schema::table('refund_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('refund_requests', 'order_id')) {
                $table->uuid('order_id')->nullable()->after('ticket_id');
            }
            if (!Schema::hasColumn('refund_requests', 'event_id')) {
                $table->uuid('event_id')->nullable()->after('order_id');
            }
            if (!Schema::hasColumn('refund_requests', 'original_amount')) {
                $table->decimal('original_amount', 10, 2)->after('approved_amount');
            }
            if (!Schema::hasColumn('refund_requests', 'refund_amount')) {
                $table->decimal('refund_amount', 10, 2)->after('original_amount');
            }
            if (!Schema::hasColumn('refund_requests', 'refund_percentage')) {
                $table->decimal('refund_percentage', 5, 2)->after('refund_amount');
            }
            if (!Schema::hasColumn('refund_requests', 'explanation')) {
                $table->text('explanation')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('refund_requests', 'refund_method')) {
                $table->string('refund_method')->default('original')->after('explanation');
            }
            if (!Schema::hasColumn('refund_requests', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('refund_requests', 'approved_by')) {
                $table->uuid('approved_by')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('refund_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('refund_requests', 'processing_started_at')) {
                $table->timestamp('processing_started_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('refund_requests', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('processing_started_at');
            }
            if (!Schema::hasColumn('refund_requests', 'payment_gateway_refund_id')) {
                $table->string('payment_gateway_refund_id')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('refund_requests', 'payment_gateway_response')) {
                $table->json('payment_gateway_response')->nullable()->after('payment_gateway_refund_id');
            }
            if (!Schema::hasColumn('refund_requests', 'appeal_count')) {
                $table->integer('appeal_count')->default(0)->after('payment_gateway_response');
            }
            if (!Schema::hasColumn('refund_requests', 'last_appeal_at')) {
                $table->timestamp('last_appeal_at')->nullable()->after('appeal_count');
            }
        });

        if (!$isSqlite && Schema::hasColumn('refund_requests', 'id')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->uuid('id')->primary()->change();
            });
        }

        if (!$this->indexExists('refund_requests', 'idx_refund_user_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_refund_user_status');
            });
        }

        if (!$this->indexExists('refund_requests', 'idx_refund_event_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index(['event_id', 'status'], 'idx_refund_event_status');
            });
        }

        if (!$this->indexExists('refund_requests', 'refund_requests_ticket_id_index')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index('ticket_id');
            });
        }

        // Update refund_policies table
        Schema::table('refund_policies', function (Blueprint $table) {
            if (!Schema::hasColumn('refund_policies', 'organizer_id')) {
                $table->uuid('organizer_id')->nullable()->after('event_id');
            }
            if (!Schema::hasColumn('refund_policies', 'refund_percentage_before_event')) {
                $table->decimal('refund_percentage_before_event', 5, 2)->default(100)->after('refund_window_days');
            }
            if (!Schema::hasColumn('refund_policies', 'refund_percentage_after_event_start')) {
                $table->decimal('refund_percentage_after_event_start', 5, 2)->default(0)->after('refund_percentage_before_event');
            }
            if (!Schema::hasColumn('refund_policies', 'allow_refunds_after_event_start')) {
                $table->boolean('allow_refunds_after_event_start')->default(false)->after('refund_percentage_after_event_start');
            }
            if (!Schema::hasColumn('refund_policies', 'processing_time_business_days')) {
                $table->integer('processing_time_business_days')->default(3)->after('allow_refunds_after_event_start');
            }
            if (!Schema::hasColumn('refund_policies', 'allowed_refund_methods')) {
                $table->json('allowed_refund_methods')->nullable()->after('processing_time_business_days');
            }
            if (!Schema::hasColumn('refund_policies', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false)->after('allowed_refund_methods');
            }
            if (!Schema::hasColumn('refund_policies', 'auto_approve_threshold')) {
                $table->decimal('auto_approve_threshold', 10, 2)->nullable()->after('requires_approval');
            }
            if (!Schema::hasColumn('refund_policies', 'max_refunds_per_user')) {
                $table->integer('max_refunds_per_user')->nullable()->after('auto_approve_threshold');
            }
            if (!Schema::hasColumn('refund_policies', 'refund_reasons')) {
                $table->json('refund_reasons')->nullable()->after('max_refunds_per_user');
            }
            if (!Schema::hasColumn('refund_policies', 'cancellation_policy')) {
                $table->text('cancellation_policy')->nullable()->after('refund_reasons');
            }
        });

        if (!$isSqlite && Schema::hasColumn('refund_policies', 'id')) {
            Schema::table('refund_policies', function (Blueprint $table) {
                $table->uuid('id')->primary()->change();
            });
        }

        // Update refund_appeals table
        Schema::table('refund_appeals', function (Blueprint $table) {
            if (!Schema::hasColumn('refund_appeals', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('refund_appeals', 'reviewed_by')) {
                $table->uuid('reviewed_by')->nullable()->after('review_notes');
            }
            if (!Schema::hasColumn('refund_appeals', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        if (!$isSqlite && Schema::hasColumn('refund_appeals', 'id')) {
            Schema::table('refund_appeals', function (Blueprint $table) {
                $table->uuid('id')->primary()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            if (Schema::hasColumn('refund_requests', 'explanation')) {
                $table->dropColumn('explanation');
            }
            if (Schema::hasColumn('refund_requests', 'refund_method')) {
                $table->dropColumn('refund_method');
            }
            if (Schema::hasColumn('refund_requests', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
            if (Schema::hasColumn('refund_requests', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('refund_requests', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('refund_requests', 'processing_started_at')) {
                $table->dropColumn('processing_started_at');
            }
            if (Schema::hasColumn('refund_requests', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
            if (Schema::hasColumn('refund_requests', 'appeal_count')) {
                $table->dropColumn('appeal_count');
            }
            if (Schema::hasColumn('refund_requests', 'last_appeal_at')) {
                $table->dropColumn('last_appeal_at');
            }
        });

        if ($this->indexExists('refund_requests', 'idx_refund_user_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropIndex('idx_refund_user_status');
            });
        }

        if ($this->indexExists('refund_requests', 'idx_refund_event_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropIndex('idx_refund_event_status');
            });
        }

        if ($this->indexExists('refund_requests', 'refund_requests_ticket_id_index')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropIndex('refund_requests_ticket_id_index');
            });
        }

        Schema::table('refund_policies', function (Blueprint $table) {
            if (Schema::hasColumn('refund_policies', 'organizer_id')) {
                $table->dropColumn('organizer_id');
            }
            if (Schema::hasColumn('refund_policies', 'refund_percentage_before_event')) {
                $table->dropColumn('refund_percentage_before_event');
            }
            if (Schema::hasColumn('refund_policies', 'refund_percentage_after_event_start')) {
                $table->dropColumn('refund_percentage_after_event_start');
            }
            if (Schema::hasColumn('refund_policies', 'allow_refunds_after_event_start')) {
                $table->dropColumn('allow_refunds_after_event_start');
            }
            if (Schema::hasColumn('refund_policies', 'processing_time_business_days')) {
                $table->dropColumn('processing_time_business_days');
            }
            if (Schema::hasColumn('refund_policies', 'allowed_refund_methods')) {
                $table->dropColumn('allowed_refund_methods');
            }
            if (Schema::hasColumn('refund_policies', 'requires_approval')) {
                $table->dropColumn('requires_approval');
            }
            if (Schema::hasColumn('refund_policies', 'auto_approve_threshold')) {
                $table->dropColumn('auto_approve_threshold');
            }
            if (Schema::hasColumn('refund_policies', 'max_refunds_per_user')) {
                $table->dropColumn('max_refunds_per_user');
            }
            if (Schema::hasColumn('refund_policies', 'refund_reasons')) {
                $table->dropColumn('refund_reasons');
            }
            if (Schema::hasColumn('refund_policies', 'cancellation_policy')) {
                $table->dropColumn('cancellation_policy');
            }
        });

        Schema::table('refund_appeals', function (Blueprint $table) {
            if (Schema::hasColumn('refund_appeals', 'review_notes')) {
                $table->dropColumn('review_notes');
            }
            if (Schema::hasColumn('refund_appeals', 'reviewed_by')) {
                $table->dropColumn('reviewed_by');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $indexName]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return $row !== null;
    }
};