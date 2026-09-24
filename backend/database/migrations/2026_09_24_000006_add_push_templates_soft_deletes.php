<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('push_notification_templates', 'deleted_at')) {
            Schema::table('push_notification_templates', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('push_notification_templates', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
