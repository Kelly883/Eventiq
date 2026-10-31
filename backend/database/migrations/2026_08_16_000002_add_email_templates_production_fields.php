<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Disable the implicit transaction wrapper for this migration.
    // On managed PostgreSQL (Render, RDS, etc.), a prior statement failure
    // in the same transaction aborts the entire transaction, causing
    // subsequent CREATE TABLE/ALTER TABLE to fail with "current transaction is aborted".
    // By opting out, each statement runs in its own autocommit transaction.
    public $withinTransaction = false;
    /**
     * Run the migrations.
     *
     * Adds production-readiness fields to email_templates:
     * - deleted_at: soft deletes for template lifecycle management
     * - published_at: nullable timestamp for draft/live workflow
     */
    public function up(): void
    {
        $hasEmailTemplatesDeletedAt = Schema::hasColumn('email_templates', 'deleted_at');
        $hasEmailTemplatesPublishedAt = Schema::hasColumn('email_templates', 'published_at');
        Schema::table('email_templates', function (Blueprint $table) use ($hasEmailTemplatesDeletedAt, $hasEmailTemplatesPublishedAt) {
            if (! $hasEmailTemplatesDeletedAt) {
                $table->softDeletes()->after('updated_at');
            }

            if (! $hasEmailTemplatesPublishedAt) {
                $table->timestamp('published_at')->nullable()->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasEmailTemplatesPublishedAt = Schema::hasColumn('email_templates', 'published_at');
        $hasEmailTemplatesDeletedAt = Schema::hasColumn('email_templates', 'deleted_at');
        Schema::table('email_templates', function (Blueprint $table) use ($hasEmailTemplatesPublishedAt, $hasEmailTemplatesDeletedAt) {
            $columns = [];

            if ($hasEmailTemplatesPublishedAt) {
                $columns[] = 'published_at';
            }
            if ($hasEmailTemplatesDeletedAt) {
                $columns[] = 'deleted_at';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
