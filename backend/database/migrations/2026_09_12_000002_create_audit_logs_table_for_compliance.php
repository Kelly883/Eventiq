<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('action');
            $table->string('target_type');
            $table->string('target_id')->nullable();
            $table->text('description')->nullable();
            $table->json('geolocation')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->json('changed_fields')->nullable();
            $table->string('status')->default('success');
            $table->string('compliance_classification')->default('internal');
            $table->json('metadata')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
