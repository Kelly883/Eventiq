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
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('type')->after('name');
            $table->longText('html_body')->after('subject');
            $table->longText('mjml_body')->nullable()->after('html_body');
            $table->json('variables')->nullable()->after('mjml_body');
            $table->boolean('is_active')->default(true)->after('variables');

            if (!Schema::hasColumn('email_templates', 'body')) {
                $table->text('body')->nullable()->change();
            }

            $table->index(['type', 'is_active'], 'idx_email_templates_type_active');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropIndex('idx_email_templates_type_active');
            $table->dropColumn(['type', 'html_body', 'mjml_body', 'variables', 'is_active']);
        });
    }
};