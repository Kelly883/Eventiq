<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permission_requests', function (Blueprint $table) {
            $table->renameColumn('user_id', 'userId');
            $table->renameColumn('permission_id', 'permissionId');
            $table->renameColumn('approved_by', 'approvedBy');
            
            $table->text('reason')->nullable()->change();
            $table->text('approvalReason')->nullable()->after('reason');
            $table->timestamp('resolvedAt')->nullable()->after('approvalReason');
            
            // Change status enum to match requirements: 'pending', 'approved', 'denied'
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending')->change();
            
            $table->index('userId');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('permission_requests', function (Blueprint $table) {
            $table->dropIndex(['userId']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            
            $table->renameColumn('userId', 'user_id');
            $table->renameColumn('permissionId', 'permission_id');
            $table->renameColumn('approvedBy', 'approved_by');
            
            $table->text('reason')->nullable()->change();
            $table->dropColumn(['approvalReason', 'resolvedAt']);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
        });
    }
};