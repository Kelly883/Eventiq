<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        if (! Schema::hasColumn('admin_settings', 'notes')) {
            Schema::table('admin_settings', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        if (Schema::hasColumn('admin_settings', 'notes')) {
            Schema::table('admin_settings', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
    }
};
