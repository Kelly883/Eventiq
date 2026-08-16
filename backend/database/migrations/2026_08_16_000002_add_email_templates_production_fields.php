<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds production-readiness fields to email_templates:
     * - deleted_at: soft deletes for template lifecycle management
     * - published_at: nullable timestamp for draft/live workflow
     */
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('email_templates', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }

            if (! Schema::hasColumn('email_templates', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('email_templates', 'published_at')) {
                $columns[] = 'published_at';
            }
            if (Schema::hasColumn('email_templates', 'deleted_at')) {
                $columns[] = 'deleted_at';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
