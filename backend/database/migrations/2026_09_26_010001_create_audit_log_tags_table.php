<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('audit_log_id');
            $table->string('tag');
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at');

            $table->unique(['audit_log_id', 'tag'], 'uniq_audit_log_tag');
            $table->index(['audit_log_id', 'tag']);
            $table->index('tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log_tags');
    }
};
