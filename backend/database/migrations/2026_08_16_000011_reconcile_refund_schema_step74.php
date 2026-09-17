<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildSqlite();
        } elseif ($driver === 'pgsql') {
            $this->fixPostgreSql();
        } else {
            $this->fixMySql();
        }
    }

    private function rebuildSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::transaction(function () {
                Schema::dropIfExists('refund_appeals');
                Schema::dropIfExists('refund_requests');
                Schema::dropIfExists('refund_policies');

                $this->createRefundPolicies();
                $this->createRefundRequests();
                $this->createRefundAppeals();
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function createRefundPolicies(): void
    {
        Schema::create('refund_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->nullable();
            $table->foreignId('organizer_id')->nullable();
            $table->integer('refund_window_days')->default(14);
            $table->decimal('refund_percentage_before_event', 5, 2);
            $table->decimal('refund_percentage_after_event_start', 5, 2)->nullable();
            $table->boolean('allow_refunds_after_event_start')->default(false);
            $table->integer('processing_time_business_days')->default(3);
            $table->json('allowed_refund_methods')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->decimal('auto_approve_threshold', 10, 2)->nullable();
            $table->integer('max_refunds_per_user')->nullable();
            $table->json('refund_reasons')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
            $table->foreign('organizer_id')->references('id')->on('organizers')->onDelete('set null');
            $table->unique('event_id');
        });
    }

    private function createRefundRequests(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('ticket_id');
            $table->uuid('order_id')->nullable();
            $table->uuid('user_id');
            $table->foreignId('event_id');
            $table->decimal('original_amount', 10, 2);
            $table->decimal('refund_amount', 10, 2);
            $table->decimal('refund_percentage', 5, 2);
            $table->string('reason');
            $table->text('explanation')->nullable();
            $table->string('refund_method');
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('payment_gateway_refund_id')->nullable();
            $table->json('payment_gateway_response')->nullable();
            $table->integer('appeal_count')->default(0);
            $table->timestamp('last_appeal_at')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['user_id', 'status']);
            $table->index(['event_id', 'status']);
            $table->index('ticket_id');
        });
    }

    private function createRefundAppeals(): void
    {
        Schema::create('refund_appeals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('refund_request_id');
            $table->uuid('user_id');
            $table->text('appeal_reason');
            $table->string('status')->default('pending');
            $table->uuid('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('refund_request_id')->references('id')->on('refund_requests')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();

            $table->index('refund_request_id');
            $table->index('user_id');
        });
    }

    private function fixMySql(): void
    {
        $hasRefundPoliciesOrganizerId = Schema::hasColumn('refund_policies', 'organizer_id');
        $hasRefundPoliciesRefundPercentageBeforeEvent = Schema::hasColumn('refund_policies', 'refund_percentage_before_event');
        $hasRefundPoliciesRefundPercentageAfterEventStart = Schema::hasColumn('refund_policies', 'refund_percentage_after_event_start');
        $hasRefundPoliciesAllowRefundsAfterEventStart = Schema::hasColumn('refund_policies', 'allow_refunds_after_event_start');
        $hasRefundPoliciesProcessingTimeBusinessDays = Schema::hasColumn('refund_policies', 'processing_time_business_days');
        $hasRefundPoliciesAllowedRefundMethods = Schema::hasColumn('refund_policies', 'allowed_refund_methods');
        $hasRefundPoliciesRequiresApproval = Schema::hasColumn('refund_policies', 'requires_approval');
        $hasRefundPoliciesAutoApproveThreshold = Schema::hasColumn('refund_policies', 'auto_approve_threshold');
        $hasRefundPoliciesMaxRefundsPerUser = Schema::hasColumn('refund_policies', 'max_refunds_per_user');
        $hasRefundPoliciesRefundReasons = Schema::hasColumn('refund_policies', 'refund_reasons');
        $hasRefundPoliciesCancellationPolicy = Schema::hasColumn('refund_policies', 'cancellation_policy');
        Schema::table('refund_policies', function (Blueprint $table) use ($hasRefundPoliciesOrganizerId, $hasRefundPoliciesRefundPercentageBeforeEvent, $hasRefundPoliciesRefundPercentageAfterEventStart, $hasRefundPoliciesAllowRefundsAfterEventStart, $hasRefundPoliciesProcessingTimeBusinessDays, $hasRefundPoliciesAllowedRefundMethods, $hasRefundPoliciesRequiresApproval, $hasRefundPoliciesAutoApproveThreshold, $hasRefundPoliciesMaxRefundsPerUser, $hasRefundPoliciesRefundReasons, $hasRefundPoliciesCancellationPolicy) {
            if (! $hasRefundPoliciesOrganizerId) {
                $table->foreignId('organizer_id')->nullable()->after('event_id');
            }
            if (! $hasRefundPoliciesRefundPercentageBeforeEvent) {
                $table->decimal('refund_percentage_before_event', 5, 2)->after('refund_window_days');
            }
            if (! $hasRefundPoliciesRefundPercentageAfterEventStart) {
                $table->decimal('refund_percentage_after_event_start', 5, 2)->nullable()->after('refund_percentage_before_event');
            }
            if (! $hasRefundPoliciesAllowRefundsAfterEventStart) {
                $table->boolean('allow_refunds_after_event_start')->default(false)->after('refund_percentage_after_event_start');
            }
            if (! $hasRefundPoliciesProcessingTimeBusinessDays) {
                $table->integer('processing_time_business_days')->default(3)->after('allow_refunds_after_event_start');
            }
            if (! $hasRefundPoliciesAllowedRefundMethods) {
                $table->json('allowed_refund_methods')->nullable()->after('processing_time_business_days');
            }
            if (! $hasRefundPoliciesRequiresApproval) {
                $table->boolean('requires_approval')->default(false)->after('allowed_refund_methods');
            }
            if (! $hasRefundPoliciesAutoApproveThreshold) {
                $table->decimal('auto_approve_threshold', 10, 2)->nullable()->after('requires_approval');
            }
            if (! $hasRefundPoliciesMaxRefundsPerUser) {
                $table->integer('max_refunds_per_user')->nullable()->after('auto_approve_threshold');
            }
            if (! $hasRefundPoliciesRefundReasons) {
                $table->json('refund_reasons')->nullable()->after('max_refunds_per_user');
            }
            if (! $hasRefundPoliciesCancellationPolicy) {
                $table->text('cancellation_policy')->nullable()->after('refund_reasons');
            }
        });

        $columnsToAddRefundRequests = [
            'order_id' => 'uuid AFTER ticket_id',
            'user_id' => 'uuid AFTER order_id',
            'event_id' => 'uuid AFTER user_id',
            'original_amount' => 'decimal(10,2) AFTER event_id',
            'refund_amount' => 'decimal(10,2) AFTER original_amount',
            'refund_percentage' => 'decimal(5,2) AFTER refund_amount',
            'explanation' => 'text AFTER reason',
            'refund_method' => 'varchar(255) AFTER explanation',
            'rejection_reason' => 'text AFTER status',
            'approved_by' => 'uuid AFTER rejection_reason',
            'approved_at' => 'timestamp NULL AFTER approved_by',
            'processing_started_at' => 'timestamp NULL AFTER approved_at',
            'completed_at' => 'timestamp NULL AFTER processing_started_at',
            'payment_gateway_refund_id' => 'varchar(255) NULL AFTER completed_at',
            'payment_gateway_response' => 'json NULL AFTER payment_gateway_refund_id',
            'appeal_count' => 'int DEFAULT 0 AFTER payment_gateway_response',
            'last_appeal_at' => 'timestamp NULL AFTER appeal_count',
        ];
        $missingRefundRequestsColumns = [];
        foreach ($columnsToAddRefundRequests as $column => $definition) {
            if (! Schema::hasColumn('refund_requests', $column)) {
                $missingRefundRequestsColumns[$column] = $definition;
            }
        }
        if (! empty($missingRefundRequestsColumns)) {
            Schema::table('refund_requests', function (Blueprint $table) use ($missingRefundRequestsColumns) {
                foreach ($missingRefundRequestsColumns as $column => $definition) {
                    $columnDef = DB::getDriverName() === 'mysql' ? $definition : preg_replace('/\s+AFTER\s+\w+/', '', $definition);
                    DB::statement("ALTER TABLE refund_requests ADD COLUMN {$column} {$columnDef}");
                }
            });
        }

        if (! $this->indexExists('refund_requests', 'idx_refund_requests_user_id_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_refund_requests_user_id_status');
            });
        }

        if (! $this->indexExists('refund_requests', 'idx_refund_requests_event_id_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index(['event_id', 'status'], 'idx_refund_requests_event_id_status');
            });
        }

        if (! $this->indexExists('refund_requests', 'refund_requests_ticket_id_index')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index('ticket_id', 'refund_requests_ticket_id_index');
            });
        }

        $hasRefundAppealsAppealReason = Schema::hasColumn('refund_appeals', 'appeal_reason');
        $hasRefundAppealsReviewedBy = Schema::hasColumn('refund_appeals', 'reviewed_by');
        $hasRefundAppealsReviewNotes = Schema::hasColumn('refund_appeals', 'review_notes');
        $hasRefundAppealsReviewedAt = Schema::hasColumn('refund_appeals', 'reviewed_at');
        Schema::table('refund_appeals', function (Blueprint $table) use ($hasRefundAppealsAppealReason, $hasRefundAppealsReviewedBy, $hasRefundAppealsReviewNotes, $hasRefundAppealsReviewedAt) {
            if (! $hasRefundAppealsAppealReason) {
                $table->text('appeal_reason')->after('user_id');
            }
            if (! $hasRefundAppealsReviewedBy) {
                $table->uuid('reviewed_by')->nullable()->after('status');
            }
            if (! $hasRefundAppealsReviewNotes) {
                $table->text('review_notes')->nullable()->after('reviewed_by');
            }
            if (! $hasRefundAppealsReviewedAt) {
                $table->timestamp('reviewed_at')->nullable()->after('review_notes');
            }
        });

        if (! $this->indexExists('refund_appeals', 'refund_appeals_refund_request_id_index')) {
            Schema::table('refund_appeals', function (Blueprint $table) {
                $table->index('refund_request_id', 'refund_appeals_refund_request_id_index');
            });
        }

        if (! $this->indexExists('refund_appeals', 'refund_appeals_user_id_index')) {
            Schema::table('refund_appeals', function (Blueprint $table) {
                $table->index('user_id', 'refund_appeals_user_id_index');
            });
        }
    }

    private function fixPostgreSql(): void
    {
        $hasRefundPoliciesOrganizerId = Schema::hasColumn('refund_policies', 'organizer_id');
        $hasRefundPoliciesRefundPercentageBeforeEvent = Schema::hasColumn('refund_policies', 'refund_percentage_before_event');
        $hasRefundPoliciesRefundPercentageAfterEventStart = Schema::hasColumn('refund_policies', 'refund_percentage_after_event_start');
        $hasRefundPoliciesAllowRefundsAfterEventStart = Schema::hasColumn('refund_policies', 'allow_refunds_after_event_start');
        $hasRefundPoliciesProcessingTimeBusinessDays = Schema::hasColumn('refund_policies', 'processing_time_business_days');
        $hasRefundPoliciesAllowedRefundMethods = Schema::hasColumn('refund_policies', 'allowed_refund_methods');
        $hasRefundPoliciesRequiresApproval = Schema::hasColumn('refund_policies', 'requires_approval');
        $hasRefundPoliciesAutoApproveThreshold = Schema::hasColumn('refund_policies', 'auto_approve_threshold');
        $hasRefundPoliciesMaxRefundsPerUser = Schema::hasColumn('refund_policies', 'max_refunds_per_user');
        $hasRefundPoliciesRefundReasons = Schema::hasColumn('refund_policies', 'refund_reasons');
        $hasRefundPoliciesCancellationPolicy = Schema::hasColumn('refund_policies', 'cancellation_policy');
        Schema::table('refund_policies', function (Blueprint $table) use ($hasRefundPoliciesOrganizerId, $hasRefundPoliciesRefundPercentageBeforeEvent, $hasRefundPoliciesRefundPercentageAfterEventStart, $hasRefundPoliciesAllowRefundsAfterEventStart, $hasRefundPoliciesProcessingTimeBusinessDays, $hasRefundPoliciesAllowedRefundMethods, $hasRefundPoliciesRequiresApproval, $hasRefundPoliciesAutoApproveThreshold, $hasRefundPoliciesMaxRefundsPerUser, $hasRefundPoliciesRefundReasons, $hasRefundPoliciesCancellationPolicy) {
            if (! $hasRefundPoliciesOrganizerId) {
                $table->foreignId('organizer_id')->nullable()->after('event_id');
            }
            if (! $hasRefundPoliciesRefundPercentageBeforeEvent) {
                $table->decimal('refund_percentage_before_event', 5, 2)->after('refund_window_days');
            }
            if (! $hasRefundPoliciesRefundPercentageAfterEventStart) {
                $table->decimal('refund_percentage_after_event_start', 5, 2)->nullable()->after('refund_percentage_before_event');
            }
            if (! $hasRefundPoliciesAllowRefundsAfterEventStart) {
                $table->boolean('allow_refunds_after_event_start')->default(false)->after('refund_percentage_after_event_start');
            }
            if (! $hasRefundPoliciesProcessingTimeBusinessDays) {
                $table->integer('processing_time_business_days')->default(3)->after('allow_refunds_after_event_start');
            }
            if (! $hasRefundPoliciesAllowedRefundMethods) {
                $table->json('allowed_refund_methods')->nullable()->after('processing_time_business_days');
            }
            if (! $hasRefundPoliciesRequiresApproval) {
                $table->boolean('requires_approval')->default(false)->after('allowed_refund_methods');
            }
            if (! $hasRefundPoliciesAutoApproveThreshold) {
                $table->decimal('auto_approve_threshold', 10, 2)->nullable()->after('requires_approval');
            }
            if (! $hasRefundPoliciesMaxRefundsPerUser) {
                $table->integer('max_refunds_per_user')->nullable()->after('auto_approve_threshold');
            }
            if (! $hasRefundPoliciesRefundReasons) {
                $table->json('refund_reasons')->nullable()->after('max_refunds_per_user');
            }
            if (! $hasRefundPoliciesCancellationPolicy) {
                $table->text('cancellation_policy')->nullable()->after('refund_reasons');
            }
        });

        $columnsToAddRefundRequests = [
            'order_id' => 'uuid AFTER ticket_id',
            'user_id' => 'uuid AFTER order_id',
            'event_id' => 'uuid AFTER user_id',
            'original_amount' => 'decimal(10,2) AFTER event_id',
            'refund_amount' => 'decimal(10,2) AFTER original_amount',
            'refund_percentage' => 'decimal(5,2) AFTER refund_amount',
            'explanation' => 'text AFTER reason',
            'refund_method' => 'varchar(255) AFTER explanation',
            'rejection_reason' => 'text AFTER status',
            'approved_by' => 'uuid AFTER rejection_reason',
            'approved_at' => 'timestamp NULL AFTER approved_by',
            'processing_started_at' => 'timestamp NULL AFTER approved_at',
            'completed_at' => 'timestamp NULL AFTER processing_started_at',
            'payment_gateway_refund_id' => 'varchar(255) NULL AFTER completed_at',
            'payment_gateway_response' => 'json NULL AFTER payment_gateway_refund_id',
            'appeal_count' => 'int DEFAULT 0 AFTER payment_gateway_response',
            'last_appeal_at' => 'timestamp NULL AFTER appeal_count',
        ];
        $missingRefundRequestsColumns = [];
        foreach ($columnsToAddRefundRequests as $column => $definition) {
            if (! Schema::hasColumn('refund_requests', $column)) {
                $missingRefundRequestsColumns[$column] = $definition;
            }
        }
        if (! empty($missingRefundRequestsColumns)) {
            Schema::table('refund_requests', function (Blueprint $table) use ($missingRefundRequestsColumns) {
                foreach ($missingRefundRequestsColumns as $column => $definition) {
                    $columnDef = preg_replace('/\s+AFTER\s+\w+/', '', $definition);
                    DB::statement("ALTER TABLE refund_requests ADD COLUMN {$column} {$columnDef}");
                }
            });
        }

        if (! $this->indexExists('refund_requests', 'idx_refund_requests_user_id_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_refund_requests_user_id_status');
            });
        }

        if (! $this->indexExists('refund_requests', 'idx_refund_requests_event_id_status')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index(['event_id', 'status'], 'idx_refund_requests_event_id_status');
            });
        }

        if (! $this->indexExists('refund_requests', 'refund_requests_ticket_id_index')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index('ticket_id', 'refund_requests_ticket_id_index');
            });
        }

        $hasRefundAppealsAppealReason = Schema::hasColumn('refund_appeals', 'appeal_reason');
        $hasRefundAppealsReviewedBy = Schema::hasColumn('refund_appeals', 'reviewed_by');
        $hasRefundAppealsReviewNotes = Schema::hasColumn('refund_appeals', 'review_notes');
        $hasRefundAppealsReviewedAt = Schema::hasColumn('refund_appeals', 'reviewed_at');
        Schema::table('refund_appeals', function (Blueprint $table) use ($hasRefundAppealsAppealReason, $hasRefundAppealsReviewedBy, $hasRefundAppealsReviewNotes, $hasRefundAppealsReviewedAt) {
            if (! $hasRefundAppealsAppealReason) {
                $table->text('appeal_reason')->after('user_id');
            }
            if (! $hasRefundAppealsReviewedBy) {
                $table->uuid('reviewed_by')->nullable()->after('status');
            }
            if (! $hasRefundAppealsReviewNotes) {
                $table->text('review_notes')->nullable()->after('reviewed_by');
            }
            if (! $hasRefundAppealsReviewedAt) {
                $table->timestamp('reviewed_at')->nullable()->after('review_notes');
            }
        });

        if (! $this->indexExists('refund_appeals', 'refund_appeals_refund_request_id_index')) {
            Schema::table('refund_appeals', function (Blueprint $table) {
                $table->index('refund_request_id', 'refund_appeals_refund_request_id_index');
            });
        }

        if (! $this->indexExists('refund_appeals', 'refund_appeals_user_id_index')) {
            Schema::table('refund_appeals', function (Blueprint $table) {
                $table->index('user_id', 'refund_appeals_user_id_index');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $index]
            );

            return $row !== null;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return $row !== null;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_appeals');
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('refund_policies');
    }
};
