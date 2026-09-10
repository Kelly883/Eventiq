<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_role_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('requester_id')->index();
            $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('target_user_id')->index();
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->uuid('approved_by')->nullable()->index();
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['target_user_id', 'role_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_role_requests');
    }
};
