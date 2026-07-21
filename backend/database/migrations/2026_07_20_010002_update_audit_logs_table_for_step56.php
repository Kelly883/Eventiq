<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->uuid('adminId')->nullable()->after('user_id');
            $table->uuid('targetUserId')->nullable()->after('adminId');
            $table->text('reason')->nullable()->after('changes');
            $table->index('adminId');
            $table->index('targetUserId');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['adminId']);
            $table->dropIndex(['targetUserId']);
            $table->dropIndex(['created_at']);
            $table->dropColumn(['adminId', 'targetUserId', 'reason']);
        });
    }
};