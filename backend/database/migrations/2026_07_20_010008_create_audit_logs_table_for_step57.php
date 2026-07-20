<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('audit_logs');

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('adminId')->nullable();
            $table->uuid('targetUserId')->nullable();
            $table->string('action');
            $table->json('oldValue')->nullable();
            $table->json('newValue')->nullable();
            $table->timestamps();

            $table->index(['targetUserId', 'created_at']);

            $table->foreign('adminId')->references('id')->on('users')->nullOnDelete();
            $table->foreign('targetUserId')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('entity');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('changes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('request_id')->nullable()->index();
            $table->timestamps();
        });
    }
};