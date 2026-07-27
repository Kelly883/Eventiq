<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('is_active');
            $table->string('category')->nullable()->after('version');
            $table->text('description')->nullable()->after('category');
            $table->longText('preview_html')->nullable()->after('description');
            
            // Add composite index for common queries
            $table->index(['type', 'is_active', 'created_at'], 'idx_email_templates_type_active_created');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropIndex('idx_email_templates_type_active_created');
            $table->dropColumn(['version', 'category', 'description', 'preview_html']);
        });
    }
};